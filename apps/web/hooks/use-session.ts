"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useRouter } from "next/navigation";
import type { User } from "@laravel-boilerplate/api-client";
import { bff } from "@/lib/http";

export const currentUserKey = ["current-user"] as const;

export function useCurrentUser() {
  return useQuery({
    queryKey: currentUserKey,
    queryFn: () => bff<User>("users/me"),
  });
}

export function useLogout() {
  const router = useRouter();
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: () => bff<void>("auth/logout", { method: "POST" }),
    onSuccess: async () => {
      // Отменяем активные запросы и убираем данные до навигации:
      // иначе useCurrentUser успеет получить 401 и показать ошибку
      await queryClient.cancelQueries({ queryKey: currentUserKey });
      queryClient.removeQueries({ queryKey: currentUserKey });

      router.replace("/login");
      router.refresh();
    },
  });
}
