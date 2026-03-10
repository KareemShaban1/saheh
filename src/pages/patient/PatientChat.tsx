import { useEffect, useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Smile } from "lucide-react";
import { useAuth } from "@/contexts/AuthContext";
import { patientApi } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { ScrollArea } from "@/components/ui/scroll-area";
import { Badge } from "@/components/ui/badge";
import { Label } from "@/components/ui/label";
import { useToast } from "@/hooks/use-toast";

type Contact = { id: number; type: "user"; name: string; subtitle?: string };
type Conversation = {
  id: number;
  target_type: "user";
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
  created_at?: string;
};

const QUICK_EMOJIS = ["😀", "😁", "😂", "😊", "😍", "🙏", "👍", "👎", "👏", "❤️", "🔥", "🎉"];

export default function PatientChat() {
  const queryClient = useQueryClient();
  const { toast } = useToast();
  const { token } = useAuth();
  const [selectedChatId, setSelectedChatId] = useState<number | null>(null);
  const [search, setSearch] = useState("");
  const [messageText, setMessageText] = useState("");
  const [imageFile, setImageFile] = useState<File | null>(null);
  const [emojiOpen, setEmojiOpen] = useState(false);

  const contactsQuery = useQuery({
    queryKey: ["patient-chat", "contacts"],
    queryFn: () => patientApi.chatContacts(token!),
    enabled: !!token,
    refetchInterval: 10000,
  });
  const conversationsQuery = useQuery({
    queryKey: ["patient-chat", "conversations"],
    queryFn: () => patientApi.chatConversations(token!),
    enabled: !!token,
    refetchInterval: 3000,
  });
  const messagesQuery = useQuery({
    queryKey: ["patient-chat", "messages", selectedChatId],
    queryFn: () => patientApi.chatMessages(token!, selectedChatId!),
    enabled: !!token && selectedChatId !== null,
    refetchInterval: 2000,
  });

  const contacts = useMemo<Contact[]>(() => {
    const root = (contactsQuery.data as { data?: unknown })?.data ?? contactsQuery.data;
    const payload = (root ?? {}) as { users?: Contact[] };
    return Array.isArray(payload.users) ? payload.users : [];
  }, [contactsQuery.data]);

  const conversations = useMemo<Conversation[]>(() => {
    const root = (conversationsQuery.data as { data?: unknown })?.data ?? conversationsQuery.data;
    return Array.isArray(root) ? (root as Conversation[]) : [];
  }, [conversationsQuery.data]);

  const messages = useMemo<Message[]>(() => {
    const root = (messagesQuery.data as { data?: unknown })?.data ?? messagesQuery.data;
    return Array.isArray(root) ? (root as Message[]) : [];
  }, [messagesQuery.data]);

  const openConversationMutation = useMutation({
    mutationFn: (payload: { target_type: "user"; target_id: number }) =>
      patientApi.openChatConversation(token!, payload),
    onSuccess: async (res) => {
      const root = (res as { data?: unknown })?.data ?? {};
      const data = root as { chat_id?: number };
      if (data.chat_id) setSelectedChatId(data.chat_id);
      await queryClient.invalidateQueries({ queryKey: ["patient-chat", "conversations"] });
    },
    onError: (e) => toast({ title: "Failed to open conversation", description: e instanceof Error ? e.message : "Unknown error", variant: "destructive" }),
  });

  const sendMessageMutation = useMutation({
    mutationFn: async () => {
      if (!token || !selectedChatId) throw new Error("No conversation selected");
      if (!messageText.trim() && !imageFile) throw new Error("Type a message or attach image");

      if (imageFile) {
        const fd = new FormData();
        fd.append("message", messageText.trim());
        fd.append("image", imageFile);
        return patientApi.sendChatMessage(token, selectedChatId, fd);
      }
      return patientApi.sendChatMessage(token, selectedChatId, { message: messageText.trim() });
    },
    onSuccess: async () => {
      setMessageText("");
      setImageFile(null);
      await queryClient.invalidateQueries({ queryKey: ["patient-chat", "messages", selectedChatId] });
      await queryClient.invalidateQueries({ queryKey: ["patient-chat", "conversations"] });
    },
    onError: (e) => toast({ title: "Failed to send message", description: e instanceof Error ? e.message : "Unknown error", variant: "destructive" }),
  });

  const filteredConversations = conversations.filter((c) =>
    `${c.title ?? ""} ${c.last_message ?? ""}`.toLowerCase().includes(search.toLowerCase()),
  );

  useEffect(() => {
    if (selectedChatId) return;
    if (conversations.length > 0) setSelectedChatId(conversations[0].id);
  }, [conversations, selectedChatId]);

  const appendEmoji = (emoji: string) => {
    setMessageText((prev) => `${prev}${emoji}`);
  };

  return (
    <div className="space-y-4">
      <div>
        <h2 className="text-2xl font-bold">Chat</h2>
        <p className="text-sm text-muted-foreground">Chat with your assigned medical teams</p>
      </div>

      <div className="grid lg:grid-cols-3 gap-4">
        <div className="rounded-xl border bg-card p-3 space-y-3">
          <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Search conversations..." />
          <ScrollArea className="h-56 sm:h-72 pr-2">
            <div className="space-y-2">
              {filteredConversations.map((conv) => (
                <button
                  key={conv.id}
                  type="button"
                  onClick={() => setSelectedChatId(conv.id)}
                  className={`w-full text-left rounded-lg border p-2 ${selectedChatId === conv.id ? "bg-muted border-primary/40" : "hover:bg-muted/50"}`}
                >
                  <div className="flex items-center justify-between gap-2">
                    <p className="font-medium truncate">{conv.title}</p>
                    {conv.unread ? <Badge variant="secondary">{conv.unread}</Badge> : null}
                  </div>
                  <p className="text-xs text-muted-foreground truncate">{conv.last_message || "No messages yet"}</p>
                </button>
              ))}
            </div>
          </ScrollArea>

          <div className="space-y-2">
            <Label>Start New Conversation</Label>
            <ScrollArea className="h-36 sm:h-40 pr-2">
              <div className="space-y-2">
                {contacts.map((c) => (
                  <button
                    key={c.id}
                    type="button"
                    className="w-full text-left rounded-lg border p-2 hover:bg-muted/50"
                    onClick={() => openConversationMutation.mutate({ target_type: "user", target_id: c.id })}
                  >
                    <p className="font-medium text-sm">{c.name}</p>
                    <p className="text-xs text-muted-foreground">{c.subtitle || "Medical team"}</p>
                  </button>
                ))}
              </div>
            </ScrollArea>
          </div>
        </div>

        <div className="lg:col-span-2 rounded-xl border bg-card p-3 space-y-3">
          <ScrollArea className="h-[300px] sm:h-[430px] pr-2">
            <div className="space-y-3">
              {selectedChatId === null && <p className="text-sm text-muted-foreground">Select or start a conversation.</p>}
              {messages.map((m) => (
                <div key={m.id} className={`flex ${m.is_mine ? "justify-end" : "justify-start"}`}>
                  <div className={`max-w-[78%] rounded-lg p-2 ${m.is_mine ? "bg-primary text-primary-foreground" : "bg-muted"}`}>
                    {m.message ? <p className="text-sm whitespace-pre-wrap">{m.message}</p> : null}
                    {m.image_url ? <img src={m.image_url} alt="attachment" className="mt-2 max-h-56 rounded border object-cover" /> : null}
                    <p className={`text-[10px] mt-1 ${m.is_mine ? "text-primary-foreground/80" : "text-muted-foreground"}`}>{m.created_at || ""}</p>
                  </div>
                </div>
              ))}
            </div>
          </ScrollArea>

          <div className="space-y-2">
            <Label>Message</Label>
            <Textarea
              rows={3}
              placeholder="Type your message..."
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
                      title={`Insert ${emoji}`}
                    >
                      {emoji}
                    </button>
                  ))}
                </div>
              </div>
            ) : null}
            <div className="flex flex-wrap items-center gap-2">
              <Button
                type="button"
                variant="outline"
                size="icon"
                onClick={() => setEmojiOpen((v) => !v)}
                disabled={selectedChatId === null}
                title="Toggle emoji picker"
              >
                <Smile className="h-4 w-4" />
              </Button>
              <Input className="flex-1 min-w-[180px]" type="file" accept="image/*" onChange={(e) => setImageFile(e.target.files?.[0] ?? null)} disabled={selectedChatId === null} />
              <Button className="w-full sm:w-auto" onClick={() => sendMessageMutation.mutate()} disabled={selectedChatId === null || sendMessageMutation.isPending}>
                Send
              </Button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
