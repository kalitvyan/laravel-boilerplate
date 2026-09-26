import { Suspense } from "react";
import Link from "next/link";
import { AuthForm } from "@/components/auth-form";

export default function RegisterPage() {
  return (
    <div className="flex w-full max-w-sm flex-col gap-4">
      {/* useSearchParams требует Suspense при статической отрисовке */}
      <Suspense>
        <AuthForm
          mode="register"
          title="Create account"
          description="Choose an email and a password"
          submitLabel="Create account"
        />
      </Suspense>
      <p className="text-muted-foreground text-center text-sm">
        Already have an account?{" "}
        <Link href="/login" className="underline">
          Sign in
        </Link>
      </p>
    </div>
  );
}
