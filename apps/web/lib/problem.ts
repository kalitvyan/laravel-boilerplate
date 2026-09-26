import { isProblemDetails, isValidationProblem, type ProblemDetails } from "@laravel-boilerplate/api-client";

export interface FieldError {
  field: string;
  message: string;
}

export interface ApiFailure {
  code: string;
  message: string;
  status: number;
  fieldErrors: FieldError[];
}

const FALLBACK: ApiFailure = {
  code: "internal_error",
  message: "Something went wrong. Please try again.",
  status: 500,
  fieldErrors: [],
};

export function toApiFailure(value: unknown, status = 500): ApiFailure {
  if (!isProblemDetails(value)) {
    return { ...FALLBACK, status };
  }

  const problem = value as ProblemDetails;

  return {
    code: problem.code,
    message: problem.detail ?? problem.title,
    status: problem.status,
    fieldErrors: isValidationProblem(value)
      ? value.errors.map((error) => ({
          // RFC 6901 pointer -> имя поля формы: /email -> email
          field: error.pointer.replace(/^\//, "").replaceAll("/", "."),
          message: error.message,
        }))
      : [],
  };
}
