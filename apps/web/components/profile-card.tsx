"use client";

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { useCurrentUser, useLogout } from "@/hooks/use-session";

export function ProfileCard() {
  const { data: user, isPending, error } = useCurrentUser();
  const logout = useLogout();

  // Во время выхода данные уже сброшены, а навигация ещё не случилась
  if (isPending || logout.isPending || logout.isSuccess) {
    return <p className="text-muted-foreground text-sm">Loading…</p>;
  }

  if (error || !user) {
    return <p className="text-destructive text-sm">Could not load the profile.</p>;
  }

  return (
    <Card className="w-full max-w-md">
      <CardHeader>
        <CardTitle>{user.email}</CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        <dl className="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm">
          <dt className="text-muted-foreground">Status</dt>
          <dd>{user.status}</dd>
          <dt className="text-muted-foreground">Roles</dt>
          <dd>{user.roles.join(", ") || "—"}</dd>
          <dt className="text-muted-foreground">Registered</dt>
          <dd>{new Date(user.registeredAt).toLocaleString()}</dd>
        </dl>

        <Button variant="secondary" onClick={() => logout.mutate()} disabled={logout.isPending}>
          {logout.isPending ? "Signing out…" : "Sign out"}
        </Button>
      </CardContent>
    </Card>
  );
}
