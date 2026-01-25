import React, { Component } from 'react';

class ErrorBoundary extends Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false, error: null };
    }

    static getDerivedStateFromError(error) {
        return { hasError: true, error };
    }

    componentDidCatch(error, errorInfo) {
        console.error("Uncaught error:", error, errorInfo);
    }

    handleReload = () => {
        window.location.reload();
    }

    render() {
        if (this.state.hasError) {
            return (
                <div style={{
                    height: '100vh',
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    justifyContent: 'center',
                    backgroundColor: '#fffdf9',
                    color: '#800000',
                    textAlign: 'center',
                    padding: '20px'
                }}>
                    <i className="fas fa-exclamation-triangle fa-5x mb-4"></i>
                    <h1 style={{ fontWeight: 800 }}>Oops, Something went wrong.</h1>
                    <p className="lead mb-4">The POS system encountered an unexpected error.</p>
                    
                    <button 
                        onClick={this.handleReload}
                        className="btn btn-lg btn-maroon-solid shadow-sm px-5"
                        style={{ borderRadius: '30px' }}
                    >
                        <i className="fas fa-sync-alt mr-2"></i> Reload System
                    </button>
                    
                    <details className="mt-5 text-muted small" style={{ maxWidth: '600px', textAlign: 'left' }}>
                        <summary>Technical Details</summary>
                        <pre className="mt-2 p-3 bg-light rounded border">
                            {this.state.error?.toString()}
                        </pre>
                    </details>
                </div>
            );
        }

        return this.props.children;
    }
}

export default ErrorBoundary;
