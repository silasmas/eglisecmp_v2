import { Component, type ErrorInfo, type ReactNode } from 'react';

type SpaErrorBoundaryProps = {
  children: ReactNode;
};

type SpaErrorBoundaryState = {
  hasError: boolean;
  message: string;
};

/**
 * Empêche une erreur React non gérée de laisser une page blanche.
 */
export default class SpaErrorBoundary extends Component<SpaErrorBoundaryProps, SpaErrorBoundaryState> {
  constructor(props: SpaErrorBoundaryProps) {
    super(props);
    this.state = { hasError: false, message: '' };
  }

  static getDerivedStateFromError(error: Error): SpaErrorBoundaryState {
    return {
      hasError: true,
      message: error?.message ?? 'Erreur inattendue',
    };
  }

  componentDidCatch(error: Error, info: ErrorInfo): void {
    console.error('[SPA]', error, info.componentStack);
  }

  render() {
    if (this.state.hasError) {
      return (
        <div
          style={{
            minHeight: '100vh',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            padding: '24px',
            background: '#faf7f5',
            color: '#1c1917',
            fontFamily: 'system-ui, sans-serif',
          }}
        >
          <div style={{ maxWidth: 420, textAlign: 'center' }}>
            <h1 style={{ fontSize: 22, marginBottom: 8 }}>Impossible d’afficher la page</h1>
            <p style={{ fontSize: 14, opacity: 0.75, marginBottom: 20 }}>
              Rechargez la page. Si le problème continue, videz le cache du navigateur.
            </p>
            <button
              type="button"
              onClick={() => window.location.reload()}
              style={{
                background: '#7f1d1d',
                color: '#fff',
                border: 0,
                borderRadius: 12,
                padding: '12px 20px',
                fontWeight: 600,
                cursor: 'pointer',
              }}
            >
              Recharger
            </button>
          </div>
        </div>
      );
    }

    return this.props.children;
  }
}
