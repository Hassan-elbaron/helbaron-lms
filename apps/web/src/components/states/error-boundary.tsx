"use client";

import { Component, type ErrorInfo, type ReactNode } from "react";
import { ErrorState } from "./error-state";

interface Props {
  children: ReactNode;
  fallback?: ReactNode;
}
interface State {
  hasError: boolean;
  message?: string;
}

/** Generic client error boundary. Wrap route subtrees or risky widgets. */
export class ErrorBoundary extends Component<Props, State> {
  override state: State = { hasError: false };

  static getDerivedStateFromError(error: Error): State {
    return { hasError: true, message: error.message };
  }

  override componentDidCatch(error: Error, info: ErrorInfo): void {
    if (process.env.NODE_ENV !== "production") console.error("ErrorBoundary caught", error, info);
  }

  reset = () => this.setState({ hasError: false, message: undefined });

  override render() {
    if (this.state.hasError) {
      return this.props.fallback ?? <ErrorState message={this.state.message} onRetry={this.reset} />;
    }
    return this.props.children;
  }
}
