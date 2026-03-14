import { useEffect, useMemo, useRef, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Mic, Smile, Square, Trash2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { ScrollArea } from "@/components/ui/scroll-area";
import { Badge } from "@/components/ui/badge";
import { organizationChatApi } from "@/lib/api";
import { useToast } from "@/hooks/use-toast";
import { useLanguage } from "@/contexts/LanguageContext";
type Contact = {
  id: number;
  type: "user" | "patient";
  name: string;
  subtitle?: string;
};

type Conversation = {
  id: number;
  target_type: "user" | "patient";
  target_id: number;
  title: string;
  last_message?: string;
  updated_at?: string;
  unread?: number;
};

type Message = {
  id: number;
  chat_id: number;
  is_mine: boolean;
  message?: string;
  image_url?: string | null;
  voice_url?: string | null;
  created_at?: string;
};

const QUICK_EMOJIS = ["😀", "😁", "😂", "😊", "😍", "🙏", "👍", "👎", "👏", "❤️", "🔥", "🎉"];

export default function OrganizationChatPage() {
  const { t } = useLanguage();
  const queryClient = useQueryClient();
  const { toast } = useToast();
  const [search, setSearch] = useState("");
  const [selectedChatId, setSelectedChatId] = useState<number | null>(null);
  const [messageText, setMessageText] = useState("");
  const [imageFile, setImageFile] = useState<File | null>(null);
  const [voiceFile, setVoiceFile] = useState<File | null>(null);
  const [voicePreviewUrl, setVoicePreviewUrl] = useState<string | null>(null);
  const [isRecording, setIsRecording] = useState(false);
  const [emojiOpen, setEmojiOpen] = useState(false);
  const mediaRecorderRef = useRef<MediaRecorder | null>(null);
  const mediaStreamRef = useRef<MediaStream | null>(null);
  const recordingChunksRef = useRef<Blob[]>([]);

  const contactsQuery = useQuery({
    queryKey: ["org-chat", "contacts"],
    queryFn: () => organizationChatApi.contacts(),
    refetchInterval: 10000,
  });

  const conversationsQuery = useQuery({
    queryKey: ["org-chat", "conversations"],
    queryFn: () => organizationChatApi.conversations(),
    refetchInterval: 3000,
  });

  const messagesQuery = useQuery({
    queryKey: ["org-chat", "messages", selectedChatId],
    queryFn: () => organizationChatApi.messages(selectedChatId!),
    enabled: selectedChatId !== null,
    refetchInterval: 2000,
  });

  const openConversationMutation = useMutation({
    mutationFn: (payload: { target_type: "user" | "patient"; target_id: number }) =>
      organizationChatApi.openConversation(payload),
    onSuccess: async (res) => {
      const root = (res as { data?: unknown })?.data ?? {};
      const data = root as { chat_id?: number };
      if (data.chat_id) {
        setSelectedChatId(data.chat_id);
      }
      await queryClient.invalidateQueries({ queryKey: ["org-chat", "conversations"] });
    },
    onError: (e) =>
      toast({
        title: "Failed to open conversation",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const sendMessageMutation = useMutation({
    mutationFn: async () => {
      if (!selectedChatId) throw new Error("No conversation selected");
      if (!messageText.trim() && !imageFile && !voiceFile) throw new Error("Type a message or attach media");

      if (imageFile || voiceFile) {
        const fd = new FormData();
        fd.append("message", messageText.trim());
        if (imageFile) fd.append("image", imageFile);
        if (voiceFile) fd.append("voice", voiceFile);
        return organizationChatApi.sendMessage(selectedChatId, fd);
      }
      return organizationChatApi.sendMessage(selectedChatId, { message: messageText.trim() });
    },
    onSuccess: async () => {
      setMessageText("");
      setImageFile(null);
      setVoiceFile(null);
      if (voicePreviewUrl) URL.revokeObjectURL(voicePreviewUrl);
      setVoicePreviewUrl(null);
      await queryClient.invalidateQueries({ queryKey: ["org-chat", "messages", selectedChatId] });
      await queryClient.invalidateQueries({ queryKey: ["org-chat", "conversations"] });
    },
    onError: (e) =>
      toast({
        title: "Failed to send message",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const contacts = useMemo(() => {
    const root = (contactsQuery.data as { data?: unknown })?.data ?? contactsQuery.data;
    const payload = (root ?? {}) as { users?: Contact[]; patients?: Contact[] };
    return {
      users: Array.isArray(payload.users) ? payload.users : [],
      patients: Array.isArray(payload.patients) ? payload.patients : [],
    };
  }, [contactsQuery.data]);

  const conversations = useMemo<Conversation[]>(() => {
    const root = (conversationsQuery.data as { data?: unknown })?.data ?? conversationsQuery.data;
    return Array.isArray(root) ? (root as Conversation[]) : [];
  }, [conversationsQuery.data]);

  const messages = useMemo<Message[]>(() => {
    const root = (messagesQuery.data as { data?: unknown })?.data ?? messagesQuery.data;
    return Array.isArray(root) ? (root as Message[]) : [];
  }, [messagesQuery.data]);

  const filteredConversations = conversations.filter((c) =>
    `${c.title ?? ""} ${c.last_message ?? ""}`.toLowerCase().includes(search.toLowerCase()),
  );

  useEffect(() => {
    if (selectedChatId) return;
    if (conversations.length > 0) {
      setSelectedChatId(conversations[0].id);
    }
  }, [conversations, selectedChatId]);

  const appendEmoji = (emoji: string) => {
    setMessageText((prev) => `${prev}${emoji}`);
  };

  const clearVoiceAttachment = () => {
    setVoiceFile(null);
    if (voicePreviewUrl) URL.revokeObjectURL(voicePreviewUrl);
    setVoicePreviewUrl(null);
  };

  const stopVoiceRecording = () => {
    const recorder = mediaRecorderRef.current;
    if (!recorder || recorder.state === "inactive") {
      setIsRecording(false);
      mediaStreamRef.current?.getTracks().forEach((track) => track.stop());
      mediaStreamRef.current = null;
      return;
    }
    recorder.stop();
  };

  const startVoiceRecording = async () => {
    try {
      if (!navigator.mediaDevices?.getUserMedia) {
        toast({
          title: "Recording not supported",
          description: "Your browser does not support microphone recording.",
          variant: "destructive",
        });
        return;
      }

      const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
      mediaStreamRef.current = stream;
      recordingChunksRef.current = [];

      const preferredMimeTypes = ["audio/webm;codecs=opus", "audio/webm", "audio/ogg", "audio/mp4"];
      const supportedMimeType = preferredMimeTypes.find((mime) => typeof MediaRecorder !== "undefined" && MediaRecorder.isTypeSupported(mime));
      const recorder = supportedMimeType ? new MediaRecorder(stream, { mimeType: supportedMimeType }) : new MediaRecorder(stream);
      mediaRecorderRef.current = recorder;

      recorder.ondataavailable = (event) => {
        if (event.data && event.data.size > 0) recordingChunksRef.current.push(event.data);
      };

      recorder.onstop = () => {
        const mimeType = recorder.mimeType || "audio/webm";
        const blob = new Blob(recordingChunksRef.current, { type: mimeType });
        if (blob.size > 0) {
          const extension = mimeType.includes("ogg") ? "ogg" : mimeType.includes("mp4") ? "m4a" : "webm";
          const file = new File([blob], `chat-voice-${Date.now()}.${extension}`, { type: mimeType });
          setVoiceFile(file);
          if (voicePreviewUrl) URL.revokeObjectURL(voicePreviewUrl);
          setVoicePreviewUrl(URL.createObjectURL(file));
        }
        mediaStreamRef.current?.getTracks().forEach((track) => track.stop());
        mediaStreamRef.current = null;
        recordingChunksRef.current = [];
        setIsRecording(false);
      };

      recorder.start();
      setIsRecording(true);
    } catch (error) {
      toast({
        title: "Microphone access failed",
        description: error instanceof Error ? error.message : "Unable to start recording.",
        variant: "destructive",
      });
      mediaStreamRef.current?.getTracks().forEach((track) => track.stop());
      mediaStreamRef.current = null;
      setIsRecording(false);
    }
  };

  useEffect(() => {
    return () => {
      if (voicePreviewUrl) URL.revokeObjectURL(voicePreviewUrl);
      const recorder = mediaRecorderRef.current;
      if (recorder && recorder.state !== "inactive") recorder.stop();
      mediaStreamRef.current?.getTracks().forEach((track) => track.stop());
    };
  }, [voicePreviewUrl]);

  return (
    <div className="space-y-4">
      <div>
        <h2 className="text-2xl font-bold"> {t("shared.chat.title")}</h2>
        <p className="text-sm text-muted-foreground"> {t("shared.chat.description")}</p>
      </div>

      <div className="grid lg:grid-cols-3 gap-4">
        <div className="rounded-xl border bg-card p-3 space-y-3">
          <Input
            placeholder={t("shared.chat.search")}
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
          <ScrollArea className="h-80 pr-2">
            <div className="space-y-2">
              {filteredConversations.map((conv) => (
                <button
                  type="button"
                  key={conv.id}
                  onClick={() => setSelectedChatId(conv.id)}
                  className={`w-full text-left rounded-lg border p-2 transition-colors ${
                    selectedChatId === conv.id ? "bg-muted border-primary/40" : "hover:bg-muted/50"
                  }`}
                >
                  <div className="flex items-center justify-between gap-2">
                    <p className="font-medium truncate">{conv.title}</p>
                    {conv.unread ? <Badge variant="secondary">{conv.unread}</Badge> : null}
                  </div>
                  <p className="text-xs text-muted-foreground truncate">{conv.last_message || "No messages yet"}</p>
                </button>
              ))}
              {filteredConversations.length === 0 && (
                <p className="text-sm text-muted-foreground"> {t("shared.chat.no_conversations")}</p>
              )}
            </div>
          </ScrollArea>

          <Tabs defaultValue="patients">
            <TabsList className="w-full">
              <TabsTrigger value="patients" className="flex-1"> {t("shared.chat.patients")}</TabsTrigger>
              <TabsTrigger value="users" className="flex-1"> {t("shared.chat.users")}</TabsTrigger>
            </TabsList>
            <TabsContent value="patients" className="mt-2">
              <ScrollArea className="h-44 pr-2">
                <div className="space-y-2">
                  {contacts.patients.map((c) => (
                    <button
                      type="button"
                      key={`p-${c.id}`}
                      className="w-full text-left rounded-lg border p-2 hover:bg-muted/50"
                      onClick={() => openConversationMutation.mutate({ target_type: "patient", target_id: c.id })}
                    >
                      <p className="font-medium text-sm">{c.name}</p>
                      <p className="text-xs text-muted-foreground">{c.subtitle || "Patient"}</p>
                    </button>
                  ))}
                </div>
              </ScrollArea>
            </TabsContent>
            <TabsContent value="users" className="mt-2">
              <ScrollArea className="h-44 pr-2">
                <div className="space-y-2">
                  {contacts.users.map((c) => (
                    <button
                      type="button"
                      key={`u-${c.id}`}
                      className="w-full text-left rounded-lg border p-2 hover:bg-muted/50"
                      onClick={() => openConversationMutation.mutate({ target_type: "user", target_id: c.id })}
                    >
                      <p className="font-medium text-sm">{c.name}</p>
                      <p className="text-xs text-muted-foreground">{c.subtitle || "User"}</p>
                    </button>
                  ))}
                </div>
              </ScrollArea>
            </TabsContent>
          </Tabs>
        </div>

        <div className="lg:col-span-2 rounded-xl border bg-card p-3 space-y-3">
          <ScrollArea className="h-[460px] pr-2">
            <div className="space-y-3">
              {selectedChatId === null && (
                <p className="text-sm text-muted-foreground"> {t("shared.chat.select_or_start_conversation")}</p>
              )}
              {messages.map((m) => (
                <div key={m.id} className={`flex ${m.is_mine ? "justify-end" : "justify-start"}`}>
                  <div className={`max-w-[78%] rounded-lg p-2 ${m.is_mine ? "bg-primary text-primary-foreground" : "bg-muted"}`}>
                    {m.message ? <p className="text-sm whitespace-pre-wrap">{m.message}</p> : null}
                    {m.image_url ? (
                      <a href={m.image_url} target="_blank" rel="noreferrer">
                        <img src={m.image_url} alt="attachment" className="mt-2 max-h-56 rounded border object-cover" />
                      </a>
                    ) : null}
                    {m.voice_url ? (
                      <audio controls className="mt-2 w-full max-w-xs">
                        <source src={m.voice_url} />
                      </audio>
                    ) : null}
                    <p className={`text-[10px] mt-1 ${m.is_mine ? "text-primary-foreground/80" : "text-muted-foreground"}`}>
                      {m.created_at || ""}
                    </p>
                  </div>
                </div>
              ))}
              {selectedChatId !== null && messages.length === 0 && (
                <p className="text-sm text-muted-foreground"> {t("shared.chat.no_messages")}</p>
              )}
            </div>
          </ScrollArea>

          <div className="space-y-2">
            <Label htmlFor="chat-message"> {t("shared.chat.message")}</Label>
            <Textarea
              id="chat-message"
              rows={3}
              placeholder={t("shared.chat.type_message")}
              value={messageText}
              onChange={(e) => setMessageText(e.target.value)}
              disabled={selectedChatId === null}
            />
            {emojiOpen ? (
              <div className="rounded-md border p-2">
                <div className="flex flex-wrap gap-1">
                  {QUICK_EMOJIS.map((emoji) => (
                    <button
                      key={emoji}
                      type="button"
                      className="h-8 w-8 rounded hover:bg-muted text-lg leading-none"
                      onClick={() => appendEmoji(emoji)}
                      title={`${t("shared.chat.insert")} ${emoji}`}
                    >
                      {emoji}
                    </button>
                  ))}
                </div>
              </div>
            ) : null}
            {voiceFile && voicePreviewUrl ? (
              <div className="rounded-md border p-2 space-y-2">
                <div className="flex items-center justify-between gap-2">
                  <p className="text-xs text-muted-foreground truncate">{voiceFile.name}</p>
                  <Button type="button" variant="ghost" size="icon" onClick={clearVoiceAttachment} title="Remove voice">
                    <Trash2 className="h-4 w-4 text-destructive" />
                  </Button>
                </div>
                <audio controls className="w-full">
                  <source src={voicePreviewUrl} type={voiceFile.type || "audio/webm"} />
                </audio>
              </div>
            ) : null}
            {isRecording ? <p className="text-xs text-destructive">Recording in progress...</p> : null}
            <div className="flex items-center gap-2">
              <Button
                type="button"
                variant="outline"
                size="icon"
                onClick={() => setEmojiOpen((v) => !v)}
                disabled={selectedChatId === null}
                title={t("shared.chat.toggle_emoji_picker")}
              >
                <Smile className="h-4 w-4" />
              </Button>
              <Input
                type="file"
                accept="image/*"
                onChange={(e) => setImageFile(e.target.files?.[0] ?? null)}
                disabled={selectedChatId === null}
              />
              <Button
                type="button"
                variant={isRecording ? "destructive" : "outline"}
                size="icon"
                onClick={isRecording ? stopVoiceRecording : startVoiceRecording}
                disabled={selectedChatId === null}
                title={isRecording ? "Stop recording" : "Record voice"}
              >
                {isRecording ? <Square className="h-4 w-4" /> : <Mic className="h-4 w-4" />}
              </Button>
              <Button
                onClick={() => sendMessageMutation.mutate()}
                disabled={selectedChatId === null || sendMessageMutation.isPending}
                className="gradient-primary text-primary-foreground border-0"
              >
                {t("shared.chat.send")}
              </Button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
