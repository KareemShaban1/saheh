import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Bell } from "lucide-react";
import { useNavigate } from "react-router-dom";
import { Button } from "@/components/ui/button";
import {
	DropdownMenu,
	DropdownMenuContent,
	DropdownMenuItem,
	DropdownMenuLabel,
	DropdownMenuSeparator,
	DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { patientApi } from "@/lib/api";

type NotificationRow = {
	id: string;
	title: string;
	message: string;
	is_read?: boolean;
	isRead?: boolean;
	read_at?: string | null;
	created_at?: string;
	action_url?: string | null;
};

function unwrapPatientNotifications(res: unknown): NotificationRow[] {
	const envelope = res as { data?: { data?: NotificationRow[] } };
	return (envelope?.data?.data ?? []) as NotificationRow[];
}

export function PatientNotificationsBell({ token }: { token: string }) {
	const navigate = useNavigate();
	const queryClient = useQueryClient();

	const { data: raw } = useQuery({
		queryKey: ["patient", "notifications", "topbar"],
		queryFn: () => patientApi.notifications(token, { per_page: "15" }),
		enabled: !!token,
		refetchInterval: 15000,
	});

	const rows = unwrapPatientNotifications(raw);
	const mapped = rows.map((item) => ({
		id: String(item.id),
		title: String(item.title ?? "Notification"),
		message: String(item.message ?? ""),
		isRead: Boolean(item.is_read ?? item.isRead ?? item.read_at),
		createdAt: String(item.created_at ?? ""),
		actionUrl: item.action_url ? String(item.action_url) : "/patient/appointments",
	}));

	const unreadCount = mapped.filter((r) => !r.isRead).length;

	const markRead = useMutation({
		mutationFn: (id: string) => patientApi.markNotificationRead(token, id),
		onSuccess: () => {
			void queryClient.invalidateQueries({ queryKey: ["patient", "notifications"] });
		},
	});

	return (
		<DropdownMenu>
			<DropdownMenuTrigger asChild>
				<Button variant="outline" size="icon" className="relative" aria-label="Notifications">
					<Bell className="h-4 w-4" />
					{unreadCount > 0 && (
						<span className="absolute -top-1 -right-1 h-5 min-w-5 px-1 rounded-full bg-destructive text-[10px] font-semibold text-destructive-foreground flex items-center justify-center">
							{unreadCount > 99 ? "99+" : unreadCount}
						</span>
					)}
				</Button>
			</DropdownMenuTrigger>
			<DropdownMenuContent align="end" className="w-80">
				<DropdownMenuLabel className="flex items-center justify-between">
					<span>Notifications</span>
					<span className="text-xs text-muted-foreground">{unreadCount} unread</span>
				</DropdownMenuLabel>
				<DropdownMenuSeparator />
				{mapped.length === 0 && <div className="px-2 py-3 text-xs text-muted-foreground">No notifications yet.</div>}
				{mapped.map((item) => (
					<DropdownMenuItem
						key={item.id}
						className="items-start py-2 cursor-pointer"
						onSelect={() => {
							if (!item.isRead) {
								void markRead.mutateAsync(item.id).catch(() => {});
							}
							navigate(item.actionUrl.startsWith("/") ? item.actionUrl : `/${item.actionUrl}`);
						}}
					>
						<div className="space-y-1">
							<p className="text-xs font-medium leading-none">{item.title}</p>
							<p className="text-xs text-muted-foreground line-clamp-3">{item.message}</p>
							{item.createdAt && (
								<p className="text-[10px] text-muted-foreground">{new Date(item.createdAt).toLocaleString()}</p>
							)}
						</div>
					</DropdownMenuItem>
				))}
				<DropdownMenuSeparator />
				<DropdownMenuItem onSelect={() => navigate("/patient/appointments")}>Appointments</DropdownMenuItem>
			</DropdownMenuContent>
		</DropdownMenu>
	);
}
