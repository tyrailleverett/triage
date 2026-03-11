import { Component } from 'react';
import type { ErrorInfo, ReactNode } from 'react';

interface ErrorBoundaryProps {
  children: ReactNode;
  fallback?: ReactNode;
}

interface ErrorBoundaryState {
  hasError: boolean;
  errorMessage: string | null;
}

export default class ErrorBoundary extends Component<ErrorBoundaryProps, ErrorBoundaryState> {
  constructor(props: ErrorBoundaryProps) {
    super(props);
    this.state = { hasError: false, errorMessage: null };
  }

  static getDerivedStateFromError(error: Error): ErrorBoundaryState {
    return { hasError: true, errorMessage: error.message };
  }

  componentDidCatch(error: Error, info: ErrorInfo): void {
    console.error('[Triage] Uncaught error:', error, info);
  }

  handleRetry = (): void => {
    this.setState({ hasError: false, errorMessage: null });
  };

  render(): ReactNode {
    if (this.state.hasError) {
      if (this.props.fallback !== undefined) {
        return this.props.fallback;
      }

      return (
        <div className="flex h-full flex-col items-center justify-center gap-4 p-8 text-center">
          <p className="text-sm font-medium text-red-400">Something went wrong.</p>
          {this.state.errorMessage !== null && (
            <p className="max-w-md text-xs text-gray-500">{this.state.errorMessage}</p>
          )}
          <button
            onClick={this.handleRetry}
            className="rounded-md border border-white/10 px-3 py-1.5 text-xs font-medium text-gray-400 hover:bg-white/10 hover:text-white"
          >
            Try again
          </button>
        </div>
      );
    }

    return this.props.children;
  }
}
