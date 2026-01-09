import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';

export default function RoleProtectedRoute({ children, allowedRoles }) {
  const { user, isAuthenticated, loading, hasRole } = useAuth();
  const location = useLocation();

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen bg-gray-100 dark:bg-gray-900">
        <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary-500"></div>
      </div>
    );
  }

  if (!isAuthenticated) {
    // Redirect to login page but save the location they were trying to access
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  // Check if user has one of the allowed roles
  const hasAllowedRole = allowedRoles.some(role => {
    return hasRole(role);
  });

  if (!hasAllowedRole) {
    // Solo redirigir si estamos en una ruta que definitivamente no corresponde al usuario
    // Evitar redirecciones en bucle
    if (user && user.role === 'doctor') {
      // Si es doctor y no está en rutas de doctor, redirigir
      if (!location.pathname.startsWith('/doctor')) {
        return <Navigate to="/doctor" replace />;
      }
    } else if (user && user.role === 'laboratorio') {
      // Si es laboratorio y está en rutas de doctor, redirigir al dashboard
      if (location.pathname.startsWith('/doctor')) {
        return <Navigate to="/" replace />;
      }
    }

    // Si llegamos aquí, mostrar un mensaje de acceso denegado en lugar de redireccionar
    return (
      <div className="flex items-center justify-center min-h-screen bg-gray-100 dark:bg-gray-900">
        <div className="text-center">
          <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-4">
            Acceso Denegado
          </h2>
          <p className="text-gray-600 dark:text-gray-400 mb-4">
            No tienes permisos para acceder a esta página.
          </p>
          <p className="text-sm text-gray-500 dark:text-gray-500 mb-6">
            Tu rol: {user?.role} | Roles permitidos: {allowedRoles.join(', ')}
          </p>
          <button
            onClick={() => window.history.back()}
            className="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded"
          >
            Volver
          </button>
        </div>
      </div>
    );
  }

  return children;
}
