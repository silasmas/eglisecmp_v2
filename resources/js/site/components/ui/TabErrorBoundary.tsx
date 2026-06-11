import { Component, type ErrorInfo, type ReactNode } from 'react';

type TabErrorBoundaryProps = {
  children: ReactNode;
  tabLabel: string;
};

type TabErrorBoundaryState = {
  hasError: boolean;
};

/**
 * Empêche un onglet défaillant de faire planter toute la page Enseignements.
 */
export default class TabErrorBoundary extends Component<TabErrorBoundaryProps, TabErrorBoundaryState> {
  constructor(props: TabErrorBoundaryProps) {
    super(props);
    this.state = { hasError: false };
  }

  static getDerivedStateFromError(): TabErrorBoundaryState {
    return { hasError: true };
  }

  componentDidCatch(error: Error, info: ErrorInfo): void {
    console.error(`[${this.props.tabLabel}]`, error, info.componentStack);
  }

  render() {
    if (this.state.hasError) {
      return (
        <div className="mx-auto max-w-lg rounded-2xl border border-burgundy-200 bg-burgundy-50 px-6 py-8 text-center">
          <p className="font-semibold text-burgundy-900">Impossible d&apos;afficher {this.props.tabLabel}</p>
          <p className="mt-2 text-sm text-burgundy-800">
            Rechargez la page. Si le problème persiste, videz le cache du navigateur ou contactez l&apos;administrateur.
          </p>
          <button
            type="button"
            onClick={() => window.location.reload()}
            className="mt-5 rounded-xl bg-burgundy-900 px-5 py-2.5 text-sm font-semibold text-white"
          >
            Recharger
          </button>
        </div>
      );
    }

    return this.props.children;
  }
}
