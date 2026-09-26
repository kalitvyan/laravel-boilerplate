import { z } from "zod";

// Границы совпадают с доменными правилами API: 12 символов минимум, 72 байта максимум
export const credentialsSchema = z.object({
  email: z.email("Enter a valid email").max(254),
  password: z
    .string()
    .min(12, "Password must be at least 12 characters")
    .refine((value) => new TextEncoder().encode(value).length <= 72, "Password must not exceed 72 bytes"),
});

export type Credentials = z.infer<typeof credentialsSchema>;
