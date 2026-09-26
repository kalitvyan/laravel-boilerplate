import { redirect } from "next/navigation";
import { ProfileCard } from "@/components/profile-card";
import { readSession } from "@/lib/session";

export default async function ProfilePage() {
  // Серверная проверка: middleware смотрит только на наличие cookie
  const session = await readSession();

  if (!session) {
    redirect("/login?next=/profile");
  }

  return (
    <main className="flex min-h-svh items-center justify-center p-4">
      <ProfileCard />
    </main>
  );
}
