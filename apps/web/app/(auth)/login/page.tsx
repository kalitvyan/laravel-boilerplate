import { Suspense } from "react";
import Link from "next/link";
import { AuthForm } from "@/components/auth-form";

export default function LoginPage() {
  return (
    <div className="flex w-full max-w-sm flex-col gap-4">
      {/* useSearchParams требует Suspense при статической отрисовке */}
      <Suspense>
        <AuthForm mode="login" title="Sign in" description="Enter your credentials" submitLabel="Sign in" />
      </Suspense>
      <p className="text-muted-foreground text-center text-sm">
        No account?{" "}
        <Link href="/register" className="underline">
          Create one
        </Link>
      </p>
    </div>
  );
}
