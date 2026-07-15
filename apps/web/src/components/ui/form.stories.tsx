import type { Meta, StoryObj } from "@storybook/react";
import {
  Form,
  FormActions,
  FormAlert,
  FormSection,
} from "@/components/ui/form";
import { FormField } from "@/components/ui/form-field";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Button } from "@/components/ui/button";

const meta = {
  title: "Forms/Form",
  component: Form,
  tags: ["autodocs"],
} satisfies Meta<typeof Form>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Example: Story = {
  render: () => (
    <Form
      className="max-w-md"
      onSubmit={(e) => e.preventDefault()}
    >
      <FormSection
        title="Contact details"
        description="Tell us how to reach you."
      >
        <FormField label="Full name" required>
          <Input placeholder="Ada Lovelace" />
        </FormField>
        <FormField label="Email address" required hint="We'll only use this to reply.">
          <Input type="email" placeholder="you@example.com" />
        </FormField>
        <FormField label="Message">
          <Textarea rows={4} placeholder="How can we help?" />
        </FormField>
      </FormSection>
      <FormActions>
        <Button type="button" variant="ghost">
          Cancel
        </Button>
        <Button type="submit">Send message</Button>
      </FormActions>
    </Form>
  ),
};

export const WithErrorAlert: Story = {
  render: () => (
    <Form className="max-w-md" onSubmit={(e) => e.preventDefault()}>
      <FormAlert variant="error">
        Your session expired. Please sign in again.
      </FormAlert>
      <FormField label="Email address" error="Invalid credentials.">
        <Input type="email" aria-invalid defaultValue="you@example.com" />
      </FormField>
      <FormField label="Password" error="Invalid credentials.">
        <Input type="password" aria-invalid />
      </FormField>
      <FormActions>
        <Button type="submit">Sign in</Button>
      </FormActions>
    </Form>
  ),
};

export const AlertVariants: Story = {
  render: () => (
    <div className="max-w-md space-y-3">
      <FormAlert variant="error">Something went wrong. Please try again.</FormAlert>
      <FormAlert variant="warning">Your trial ends in 3 days.</FormAlert>
      <FormAlert variant="success">Your changes have been saved.</FormAlert>
      <FormAlert variant="info">A new version is available.</FormAlert>
    </div>
  ),
};
