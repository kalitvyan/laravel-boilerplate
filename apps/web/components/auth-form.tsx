"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { useRouter, useSearchParams } from "next/navigation";
import { useForm } from "react-hook-form";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { ApiError, bff } from "@/lib/http";
import { credentialsSchema, type Credentials } from "@/lib/schemas";

interface AuthFormProps {
  mode: "login" | "register";
  title: string;
  description: string;
  submitLabel: string;
}

export function AuthForm({ mode, title, description, submitLabel }: AuthFormProps) {
  const router = useRouter();
  const searchParams = useSearchParams();

  const form = useForm<Credentials>({
    resolver: zodResolver(credentialsSchema),
    defaultValues: { email: "", password: "" },
  });

  async function onSubmit(values: Credentials) {
    try {
      await bff(`auth/${mode}`, { method: "POST", body: JSON.stringify(values) });

      // Открытый редирект: принимаем только относительные пути
      const next = searchParams.get("next");
      router.replace(next?.startsWith("/") && !next.startsWith("//") ? next : "/profile");
      router.refresh();
    } catch (error) {
      if (!(error instanceof ApiError)) {
        form.setError("root", { message: "Something went wrong. Please try again." });

        return;
      }

      // Ошибки валидации раскладываем по полям, остальное показываем над формой
      for (const fieldError of error.failure.fieldErrors) {
        if (fieldError.field === "email" || fieldError.field === "password") {
          form.setError(fieldError.field, { message: fieldError.message });
        }
      }

      if (error.failure.fieldErrors.length === 0) {
        form.setError("root", { message: error.failure.message });
      }
    }
  }

  return (
    <Card className="w-full max-w-sm">
      <CardHeader>
        <CardTitle>{title}</CardTitle>
        <CardDescription>{description}</CardDescription>
      </CardHeader>
      <CardContent>
        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
            <FormField
              control={form.control}
              name="email"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Email</FormLabel>
                  <FormControl>
                    <Input type="email" autoComplete="email" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="password"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Password</FormLabel>
                  <FormControl>
                    <Input
                      type="password"
                      autoComplete={mode === "login" ? "current-password" : "new-password"}
                      {...field}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            {form.formState.errors.root && (
              <p className="text-destructive text-sm">{form.formState.errors.root.message}</p>
            )}

            <Button type="submit" className="w-full" disabled={form.formState.isSubmitting}>
              {form.formState.isSubmitting ? "Please wait…" : submitLabel}
            </Button>
          </form>
        </Form>
      </CardContent>
    </Card>
  );
}
