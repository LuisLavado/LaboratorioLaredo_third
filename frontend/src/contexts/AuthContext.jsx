import { createContext, useContext, useEffect, useState } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';
import { authAPI } from '../services/api';

const AuthContext = createContext();

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(localStorage.getItem('token'));
  const [loading, setLoading] = useState(true);
  const navigate = useNavigate();

  // Set up axios defaults
  useEffect(() => {
    if (token) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
      localStorage.setItem('token', token);
    } else {
      delete axios.defaults.headers.common['Authorization'];
      localStorage.removeItem('token');
    }
  }, [token]);

  // Check if user is authenticated on initial load
  useEffect(() => {
    const checkAuth = async () => {
      if (!token) {
        setLoading(false);
        return;
      }

      try {
        // Intentar obtener el usuario del localStorage
        const storedUser = localStorage.getItem('user');

        if (storedUser) {
          try {
            const parsedUser = JSON.parse(storedUser);

            // Verificar que el usuario tenga un rol
            if (!parsedUser || !parsedUser.role) {
              console.error('Usuario sin rol definido en localStorage:', parsedUser);

              // Intentar corregir el usuario
              if (parsedUser) {
                const correctedUser = {
                  ...parsedUser,
                  role: parsedUser.role || 'laboratorio', // Asumimos laboratorio como fallback
                  name: parsedUser.name || 'Usuario'
                };

                setUser(correctedUser);
                localStorage.setItem('user', JSON.stringify(correctedUser));
              } else {
                throw new Error('Usuario inválido');
              }
            } else {
              // El usuario parece válido
              setUser(parsedUser);
            }
          } catch (parseError) {
            console.error('Error al parsear usuario del localStorage:', parseError);
            throw new Error('Error al parsear usuario');
          }
        } else {
          // Si no hay usuario en localStorage pero hay token, algo está mal
          console.error('Token presente pero no hay usuario en localStorage');
          throw new Error('No se encontró información del usuario');
        }
      } catch (error) {
        console.error('Authentication error:', error);
        logout();
      } finally {
        setLoading(false);
      }
    };

    checkAuth();
  }, []); // Dependencias vacías para que solo se ejecute una vez

  const login = async (credentials) => {
    try {
      console.log('Intentando iniciar sesión con credenciales:', { email: credentials.email, password: '******' });

      // Validar que las credenciales estén completas
      if (!credentials.email || !credentials.password) {
        console.error('Credenciales incompletas');
        return {
          success: false,
          message: 'Por favor ingrese correo electrónico y contraseña'
        };
      }

      // Realizar la petición de login
      const response = await authAPI.login(credentials);

      // Verificar que la respuesta sea válida
      if (!response || !response.data) {
        console.error('Respuesta de login inválida:', response);
        return {
          success: false,
          message: 'Error en la respuesta del servidor'
        };
      }

      const { user, access_token, token } = response.data;
      console.log('Respuesta del servidor:', response.data);

      // El token puede venir como 'access_token' o 'token'
      const authToken = access_token || token;
      
      if (!authToken) {
        console.error('No se recibió token de autenticación');
        return {
          success: false,
          message: 'Error: No se recibió token de autenticación'
        };
      }

      console.log('Token extraído:', authToken.substring(0, 15) + '...');

      // Asegurarnos de que el usuario tenga un rol definido
      if (!user || !user.role) {
        console.error('Usuario sin rol definido:', user);
        return {
          success: false,
          message: 'Error: El usuario no tiene un rol definido'
        };
      }

      // Asegurarnos de que el usuario tenga todos los campos necesarios
      const processedUser = {
        ...user,
        // Construir nombre completo a partir de los campos disponibles
        name: (() => {
          // Priorizar nombre completo si existe
          if (user.name && user.name.trim() !== '') return user.name;
          
          // Construir nombre a partir de nombre y apellido (usar los campos que realmente vienen del servidor)
          const nombre = (user.nombre || user.nombres || user.first_name || '').trim();
          const apellido = (user.apellido || user.apellidos || user.last_name || '').trim();
          
          if (nombre && apellido) {
            return `${nombre} ${apellido}`;
          } else if (nombre) {
            return nombre;
          } else if (apellido) {
            return apellido;
          }
          
          // Si tiene email, usar la parte antes del @
          if (user.email) {
            return user.email.split('@')[0];
          }
          
          // Fallback por rol
          return user.role === 'doctor' ? 'Doctor' : 'Usuario';
        })(),
        role: user.role,
        // Campos opcionales para doctores
        especialidad: user.especialidad || null,
        colegiatura: user.colegiatura || null
      };

      // Guardar en localStorage primero para evitar problemas de sincronización
      localStorage.setItem('token', authToken);
      localStorage.setItem('user', JSON.stringify(processedUser));

      // Verificar que se guardó correctamente
      const savedToken = localStorage.getItem('token');
      const savedUser = localStorage.getItem('user');
      console.log('🔍 VERIFICACIÓN POST-LOGIN:');
      console.log('🔍 Token guardado en localStorage:', savedToken ? 'SÍ' : 'NO');
      console.log('🔍 Usuario guardado en localStorage:', savedUser ? 'SÍ' : 'NO');
      console.log('🔍 Token completo:', savedToken);

      // Luego actualizar el estado
      setToken(authToken);
      setUser(processedUser);

      console.log('Usuario procesado:', processedUser);
      console.log('Rol del usuario:', processedUser.role);
      console.log('Nombre construido:', processedUser.name);
      console.log('Campos originales del usuario:', {
        name: user.name,
        nombres: user.nombres,
        nombre: user.nombre,
        apellido: user.apellido,
        email: user.email
      });
      console.log('Login exitoso, token guardado:', authToken.substring(0, 15) + '...');
      console.log('✅ Estado actualizado exitosamente. Usuario logueado:', processedUser.name);

      return { success: true };
    } catch (error) {
      console.error('Error de login:', error);

      // Manejar diferentes tipos de errores
      if (error.response) {
        // El servidor respondió con un código de error
        console.error('Error de respuesta del servidor:', error.response);

        // Manejar específicamente errores de credenciales incorrectas (401)
        if (error.response.status === 401) {
          console.log('Credenciales incorrectas detectadas');
          return {
            success: false,
            message: 'Credenciales incorrectas. Por favor verifique su correo y contraseña.'
          };
        }

        return {
          success: false,
          message: error.response.data?.message ||
                  `Error del servidor: ${error.response.status} ${error.response.statusText}`
        };
      } else if (error.request) {
        // La petición fue hecha pero no se recibió respuesta
        console.error('No se recibió respuesta del servidor:', error.request);
        return {
          success: false,
          message: 'No se pudo conectar con el servidor. Verifique su conexión a internet.'
        };
      } else {
        // Algo ocurrió al configurar la petición
        console.error('Error al configurar la petición:', error.message);
        return {
          success: false,
          message: `Error de configuración: ${error.message}`
        };
      }
    }
  };

  const register = async (userData) => {
    try {
      const response = await authAPI.register(userData);
      const { user, token } = response.data;

      setToken(token);
      setUser(user);
      localStorage.setItem('user', JSON.stringify(user));

      return { success: true };
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Error al registrar usuario'
      };
    }
  };

  const logout = async () => {
    try {
      if (token) {
        await authAPI.logout();
      }
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      setToken(null);
      setUser(null);
      localStorage.removeItem('token');
      localStorage.removeItem('user');

      // Solo navegar si no estamos ya en login
      if (window.location.pathname !== '/login') {
        navigate('/login');
      }
    }
  };

  // Check if user has a specific role
  const hasRole = (role) => {
    if (!user) return false;

    // Verificar si el rol está en user.role o en user.roles
    if (typeof user.role === 'string') {
      return user.role === role;
    } else if (Array.isArray(user.roles)) {
      return user.roles.includes(role);
    }

    // Si llegamos aquí, intentamos hacer un log para depuración
    console.log('Información del usuario:', user);
    return false;
  };

  // Check if user is a doctor
  const isDoctor = () => {
    return hasRole('doctor');
  };

  // Check if user is a lab technician
  const isLabTechnician = () => {
    return hasRole('laboratorio');
  };

  // Update user information
  const updateUser = (updatedUserData) => {
    const updatedUser = { ...user, ...updatedUserData };
    setUser(updatedUser);
    localStorage.setItem('user', JSON.stringify(updatedUser));
  };

  return (
    <AuthContext.Provider value={{
      user,
      token,
      loading,
      login,
      register,
      logout,
      updateUser,
      isAuthenticated: !!user,
      hasRole,
      isDoctor,
      isLabTechnician
    }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}
