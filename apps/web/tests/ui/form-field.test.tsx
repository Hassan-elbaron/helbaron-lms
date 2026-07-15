import { describe, expect, it } from "vitest";
import { render, screen } from "@testing-library/react";
import { FormField } from "@/components/ui/form-field";

describe("FormField", () => {
  it("wires aria-invalid + aria-describedby (hint & error) and renders an alert", () => {
    render(
      <FormField label="Email" error="Email is required" hint="We never share it" required>
        <input type="email" />
      </FormField>,
    );

    const input = screen.getByRole("textbox");
    expect(input).toHaveAttribute("aria-invalid", "true");
    expect(input).toHaveAttribute("aria-required", "true");

    const describedBy = input.getAttribute("aria-describedby") ?? "";
    const ids = describedBy.split(" ").filter(Boolean);
    expect(ids.some((i) => i.endsWith("-hint"))).toBe(true);
    expect(ids.some((i) => i.endsWith("-error"))).toBe(true);

    const alert = screen.getByRole("alert");
    expect(alert).toHaveTextContent("Email is required");
    // Visible required marker + screen-reader text.
    expect(screen.getByText("(required)")).toBeInTheDocument();
  });

  it("shows a success status (and no error) when success is provided", () => {
    render(
      <FormField label="Coupon" success="Applied!">
        <input />
      </FormField>,
    );
    const input = screen.getByRole("textbox");
    expect(input).not.toHaveAttribute("aria-invalid");
    expect(screen.getByRole("status")).toHaveTextContent("Applied!");
  });

  it("supports a render-function control", () => {
    render(
      <FormField label="Custom" error="Bad">
        {(props) => <input data-testid="ctrl" {...props} />}
      </FormField>,
    );
    const input = screen.getByTestId("ctrl");
    expect(input).toHaveAttribute("aria-invalid", "true");
    expect(input.getAttribute("aria-describedby")).toContain("-error");
  });
});
