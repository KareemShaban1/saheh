import { useState } from "react";
import { Link, useLocation, useNavigate } from "react-router-dom";
import { Shield, Stethoscope } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { setSuperAdminToken, setSuperAdminUser, superAdminAuthApi } from "@/lib/api";

export default function SuperAdminLoginPage() {
  const navigate = useNavigate();
  const location = useLocation();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  const redirectTo = (location.state as { from?: { pathname?: string } } | null)?.from?.pathname || "/super-admin";

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError("");
    setLoading(true);

    try {
      const res = await superAdminAuthApi.login(email, password);
      if (!res.token) {
        setError(res.message || "Login failed");
        return;
      }

      setSuperAdminToken(res.token);
      if (res.admin) setSuperAdminUser(res.admin);
      navigate(redirectTo, { replace: true });
    } catch (err: unknown) {
      const apiError = err as { message?: string; errors?: Record<string, string[]> };
      const firstError = apiError.errors ? Object.values(apiError.errors).flat()[0] : null;
      setError(firstError || apiError.message || "Invalid credentials");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-muted/30 p-4">
      <Card className="w-full max-w-md">
        <CardHeader className="text-center">
          <Link to="/" className="inline-flex items-center gap-2 justify-center font-bold text-xl mb-2">
            <div className="h-10 w-10 rounded-lg gradient-primary flex items-center justify-center">
              <Stethoscope className="h-5 w-5 text-primary-foreground" />
            </div>
            MediCare
          </Link>
          <div className="flex justify-center">
            <Shield className="h-10 w-10 text-primary" />
          </div>
          <CardTitle>Super Admin Login</CardTitle>
          <CardDescription>Sign in to control clinics, labs, radiology, users, and platform settings.</CardDescription>
        </CardHeader>

        <form onSubmit={handleSubmit}>
          <CardContent className="space-y-4">
            {error ? <div className="rounded-lg bg-destructive/10 text-destructive text-sm p-3">{error}</div> : null}

            <div className="space-y-2">
              <Label htmlFor="email">Email</Label>
              <Input
                id="email"
                type="email"
                placeholder="admin@example.com"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                autoComplete="email"
                required
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="password">Password</Label>
              <Input
                id="password"
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                autoComplete="current-password"
                required
              />
            </div>
          </CardContent>

          <CardFooter className="flex flex-col gap-3">
            <Button type="submit" className="w-full gradient-primary border-0" disabled={loading}>
              {loading ? "Signing in..." : "Sign in"}
            </Button>

            <Link to="/dashboard-login">
              <Button type="button" variant="ghost" size="sm">
                Back to dashboard login
              </Button>
            </Link>
          </CardFooter>
        </form>
      </Card>
    </div>
  );
}
