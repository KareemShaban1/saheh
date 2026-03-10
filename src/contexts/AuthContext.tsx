import React, { createContext, useCallback, useContext, useEffect, useState } from "react";
import { authApi } from "@/lib/api";

const TOKEN_KEY = "patient_token";
const PATIENT_KEY = "patient_user";

export interface PatientUser {
  id: number;
  name: string;
  email: string;
  phone?: string;
  address?: string;
  age?: number;
  gender?: string;
  blood_group?: string;
  whatsapp_number?: string;
  [key: string]: unknown;
}

interface AuthContextValue {
  token: string | null;
  patient: PatientUser | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  login: (email: string, password: string) => Promise<void>;
  register: (data: RegisterData) => Promise<void>;
  logout: () => Promise<void>;
  setPatient: (p: PatientUser | null) => void;
}

export interface RegisterData {
  name: string;
  email: string;
  password: string;
  age: number;
  phone: string;
  address: string;
  gender: "male" | "female";
  blood_group: string;
  whatsapp_number?: string;
}

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [token, setToken] = useState<string | null>(() => localStorage.getItem(TOKEN_KEY));
  const [patient, setPatientState] = useState<PatientUser | null>(() => {
    try {
      const raw = localStorage.getItem(PATIENT_KEY);
      return raw ? (JSON.parse(raw) as PatientUser) : null;
    } catch {
      return null;
    }
  });
  const [isLoading, setIsLoading] = useState(true);

  const setPatient = useCallback((p: PatientUser | null) => {
    setPatientState(p);
    if (p) localStorage.setItem(PATIENT_KEY, JSON.stringify(p));
    else localStorage.removeItem(PATIENT_KEY);
  }, []);

  useEffect(() => {
    if (!token) {
      setPatientState(null);
      localStorage.removeItem(TOKEN_KEY);
      localStorage.removeItem(PATIENT_KEY);
      setIsLoading(false);
      return;
    }
    localStorage.setItem(TOKEN_KEY, token);
    if (patient) {
      localStorage.setItem(PATIENT_KEY, JSON.stringify(patient));
      setIsLoading(false);
      return;
    }
    authApi
      .getProfile(token)
      .then((res) => {
        if (res.patient) setPatientState(res.patient as PatientUser);
      })
      .catch(() => {
        setToken(null);
        setPatientState(null);
        localStorage.removeItem(TOKEN_KEY);
        localStorage.removeItem(PATIENT_KEY);
      })
      .finally(() => setIsLoading(false));
  }, [token]);

  const login = useCallback(async (email: string, password: string) => {
    const res = await authApi.login(email, password);
    if (!res.token) throw new Error((res as { message?: string }).message || "Login failed");
    setToken(res.token);
    if (res.patient) setPatientState(res.patient as PatientUser);
  }, []);

  const register = useCallback(async (data: RegisterData) => {
    const res = await authApi.register(data);
    if (!res.token) throw new Error((res as { message?: string }).message || "Registration failed");
    setToken(res.token);
    if (res.patient) setPatientState(res.patient as PatientUser);
  }, []);

  const logout = useCallback(async () => {
    if (token) {
      try {
        await authApi.logout(token);
      } catch {
        // ignore
      }
    }
    setToken(null);
    setPatientState(null);
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(PATIENT_KEY);
  }, [token]);

  const value: AuthContextValue = {
    token,
    patient,
    isAuthenticated: !!token,
    isLoading,
    login,
    register,
    logout,
    setPatient,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth must be used within AuthProvider");
  return ctx;
}
