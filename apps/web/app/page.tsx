import { redirect } from "next/navigation";
import { readSession } from "@/lib/session";

export default async function Home() {
  redirect((await readSession()) ? "/profile" : "/login");
}
