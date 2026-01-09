import React, { Suspense, useEffect } from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { useAuth } from './contexts/AuthContext';
import Layout from './components/layout/Layout';
import ProtectedRoute from './components/auth/ProtectedRoute';
import RoleProtectedRoute from './components/auth/RoleProtectedRoute';
import AuthGuard from './components/auth/AuthGuard';
import Login from './pages/auth/Login';
import Register from './pages/auth/Register';
import Dashboard from './pages/Dashboard';
import NewDashboard from './pages/dashboard/NewDashboard';
import RealtimeUpdater from './components/realtime/RealtimeUpdater';
import WebSocketProvider from './components/websocket/WebSocketProvider';

// Doctor-specific components
const DoctorDashboard = React.lazy(() => import('./pages/doctor/Dashboard'));
const DoctorPatients = React.lazy(() => import('./pages/doctor/Patients'));
const DoctorNewPatient = React.lazy(() => import('./pages/doctor/NewPatient'));
const DoctorPatientDetails = React.lazy(() => import('./pages/doctor/PatientDetails'));
const DoctorEditPatient = React.lazy(() => import('./pages/doctor/EditPatient'));
const DoctorRequests = React.lazy(() => import('./pages/doctor/Requests'));
const DoctorAllRequests = React.lazy(() => import('./pages/doctor/AllRequests'));
const DoctorResults = React.lazy(() => import('./pages/doctor/Results'));
const DoctorReports = React.lazy(() => import('./pages/doctor/DoctorReports'));
const DoctorRequestDetail = React.lazy(() => import('./pages/doctor/RequestDetail'));
const DoctorRequestResults = React.lazy(() => import('./pages/doctor/RequestResults'));
const DoctorNewRequest = React.lazy(() => import('./pages/doctor/NewRequest'));
const DoctorEditRequest = React.lazy(() => import('./pages/doctor/EditRequest'));
const DoctorPrintRequest = React.lazy(() => import('./pages/doctor/PrintRequest'));
const DoctorPrintResults = React.lazy(() => import('./pages/doctor/DoctorPrintResults'));
const DoctorResultsView = React.lazy(() => import('./pages/doctor/DoctorResultsView'));

// Lazy load other pages to improve initial load time
const Patients = React.lazy(() => import('./pages/patients/Patients'));
const PatientDetails = React.lazy(() => import('./pages/patients/PatientDetails'));
const NewPatient = React.lazy(() => import('./pages/patients/NewPatient'));
const EditPatient = React.lazy(() => import('./pages/patients/EditPatient'));
const DeletedPatients = React.lazy(() => import('./pages/patients/DeletedPatients'));
const Exams = React.lazy(() => import('./pages/exams/Exams'));
const ExamDetails = React.lazy(() => import('./pages/exams/ExamDetails'));
const NewExam = React.lazy(() => import('./pages/exams/NewExam'));
const EditExam = React.lazy(() => import('./pages/exams/EditExam'));
const InactiveExams = React.lazy(() => import('./pages/exams/InactiveExams'));
const Services = React.lazy(() => import('./pages/services/Services'));
const NewService = React.lazy(() => import('./pages/services/NewService'));
const EditService = React.lazy(() => import('./pages/services/EditService'));
const InactiveServices = React.lazy(() => import('./pages/services/InactiveServices'));
const Reports = React.lazy(() => import('./pages/reports/AdvancedReports'));
const AdvancedReports = React.lazy(() => import('./pages/reports/AdvancedReports'));
const Requests = React.lazy(() => import('./pages/requests/Requests'));
const RequestDetails = React.lazy(() => import('./pages/requests/RequestDetails'));
const NewRequest = React.lazy(() => import('./pages/requests/NewRequest'));
const Results = React.lazy(() => import('./pages/results/Results'));
const ResultDetails = React.lazy(() => import('./pages/results/ResultDetails'));
const RegisterResultsDynamic = React.lazy(() => import('./pages/results/RegisterResultsDynamic'));
const PrintResults = React.lazy(() => import('./pages/results/PrintResults'));
const NotFound = React.lazy(() => import('./pages/NotFound'));
const Profile = React.lazy(() => import('./pages/profile/Profile'));
const Settings = React.lazy(() => import('./pages/profile/Settings'));
const ResultsViewDynamic = React.lazy(() => import('./pages/results/ResultsViewDynamic'));

// Loading fallback for lazy-loaded components
const LazyLoadingFallback = () => (
  <div className="flex items-center justify-center min-h-screen bg-gray-100 dark:bg-gray-900">
    <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary-500"></div>
  </div>
);

function App() {
  const { isAuthenticated } = useAuth();

  return (
    <AuthGuard>
      {/* Componentes para actualizaciones en tiempo real */}
      {isAuthenticated && (
        <>
          <RealtimeUpdater key="realtime-updater" />
          {/* <EventListener key="event-listener" /> */}
        </>
      )}

      {/* Envolver las rutas con WebSocketProvider cuando el usuario esté autenticado */}
      {isAuthenticated ? (
        <WebSocketProvider>
          <Routes>
            {/* Public routes - redirect to dashboard if authenticated, but preserve intended route */}
            <Route
              path="/login"
              element={<Navigate to="/" replace />}
            />
            <Route
              path="/register"
              element={<Navigate to="/" replace />}
            />

            {/* Protected routes */}
            <Route
              path="/"
              element={
                <ProtectedRoute>
                  <Layout />
                </ProtectedRoute>
              }
            >
        <Route index element={
          <RoleProtectedRoute allowedRoles={['laboratorio']}>
            <NewDashboard />
          </RoleProtectedRoute>
        } />

        <Route path="dashboard-old" element={
          <RoleProtectedRoute allowedRoles={['laboratorio']}>
            <Dashboard />
          </RoleProtectedRoute>
        } />

        {/* Patients routes */}
        <Route
          path="pacientes"
          element={
            <RoleProtectedRoute allowedRoles={['laboratorio']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <Patients />
              </Suspense>
            </RoleProtectedRoute>
          }
        />
        <Route
          path="pacientes/nuevo"
          element={
            <RoleProtectedRoute allowedRoles={['laboratorio']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <NewPatient />
              </Suspense>
            </RoleProtectedRoute>
          }
        />
        <Route
          path="pacientes/:id"
          element={
            <RoleProtectedRoute allowedRoles={['laboratorio']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <PatientDetails />
              </Suspense>
            </RoleProtectedRoute>
          }
        />
        <Route
          path="pacientes/:id/editar"
          element={
            <RoleProtectedRoute allowedRoles={['laboratorio']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <EditPatient />
              </Suspense>
            </RoleProtectedRoute>
          }
        />
        <Route
          path="pacientes/eliminados"
          element={
            <RoleProtectedRoute allowedRoles={['laboratorio']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <DeletedPatients />
              </Suspense>
            </RoleProtectedRoute>
          }
        />

        {/* Exams routes */}
        <Route
          path="examenes"
          element={
            <RoleProtectedRoute allowedRoles={['laboratorio']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <Exams />
              </Suspense>
            </RoleProtectedRoute>
          }
        />
        <Route
          path="examenes/nuevo"
          element={
            <RoleProtectedRoute allowedRoles={['laboratorio']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <NewExam />
              </Suspense>
            </RoleProtectedRoute>
          }
        />
        <Route
          path="examenes/:id"
          element={
            <RoleProtectedRoute allowedRoles={['laboratorio']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <ExamDetails />
              </Suspense>
            </RoleProtectedRoute>
          }
        />
        <Route
          path="examenes/:id/editar"
          element={
            <RoleProtectedRoute allowedRoles={['laboratorio']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <EditExam />
              </Suspense>
            </RoleProtectedRoute>
          }
        />
        <Route
          path="examenes/desactivados"
          element={
            <RoleProtectedRoute allowedRoles={['laboratorio']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <InactiveExams />
              </Suspense>
            </RoleProtectedRoute>
          }
        />

        {/* Services routes */}
        <Route
          path="servicios"
          element={
            <RoleProtectedRoute allowedRoles={['laboratorio']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <Services />
              </Suspense>
            </RoleProtectedRoute>
          }
        />
        <Route
          path="servicios/nuevo"
          element={
            <RoleProtectedRoute allowedRoles={['laboratorio']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <NewService />
              </Suspense>
            </RoleProtectedRoute>
          }
        />
        <Route
          path="servicios/:id/editar"
          element={
            <RoleProtectedRoute allowedRoles={['laboratorio']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <EditService />
              </Suspense>
            </RoleProtectedRoute>
          }
        />
        <Route
          path="servicios/inactivos"
          element={
            <RoleProtectedRoute allowedRoles={['laboratorio']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <InactiveServices />
              </Suspense>
            </RoleProtectedRoute>
          }
        />

        {/* Requests routes */}
        <Route
          path="solicitudes"
          element={
            <RoleProtectedRoute allowedRoles={['laboratorio']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <Requests />
              </Suspense>
            </RoleProtectedRoute>
          }
        />
        <Route
          path="solicitudes/nueva"
          element={
            <RoleProtectedRoute allowedRoles={['laboratorio']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <NewRequest />
              </Suspense>
            </RoleProtectedRoute>
          }
        />
        <Route
          path="solicitudes/:id"
          element={
            <RoleProtectedRoute allowedRoles={['laboratorio']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <RequestDetails />
              </Suspense>
            </RoleProtectedRoute>
          }
        />

        {/* Results routes */}
        <Route
          path="resultados"
          element={
            <RoleProtectedRoute allowedRoles={['laboratorio']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <Results />
              </Suspense>
            </RoleProtectedRoute>
          }
        />
        <Route
          path="reportes"
          element={
            <RoleProtectedRoute allowedRoles={['laboratorio', 'doctor']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <Reports />
              </Suspense>
            </RoleProtectedRoute>
          }
        />
        <Route
          path="reportes/avanzados"
          element={
            <RoleProtectedRoute allowedRoles={['laboratorio', 'doctor']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <AdvancedReports />
              </Suspense>
            </RoleProtectedRoute>
          }
        />
        <Route
          path="resultados/:id/imprimir"
          element={
            <RoleProtectedRoute allowedRoles={['laboratorio']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <PrintResults />
              </Suspense>
            </RoleProtectedRoute>
          }
        />
        <Route
          path="resultados/:id"
          element={
            <RoleProtectedRoute allowedRoles={['laboratorio']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <RegisterResultsDynamic />
              </Suspense>
            </RoleProtectedRoute>
          }
        />



        {/* Doctor routes */}
        <Route
          path="doctor"
          element={
            <RoleProtectedRoute allowedRoles={['doctor']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <DoctorDashboard />
              </Suspense>
            </RoleProtectedRoute>
          }
        />
        <Route
          path="doctor/pacientes"
          element={
            <RoleProtectedRoute allowedRoles={['doctor']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <DoctorPatients />
              </Suspense>
            </RoleProtectedRoute>
          }
        />
        <Route
          path="doctor/pacientes/nuevo"
          element={
            <RoleProtectedRoute allowedRoles={['doctor']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <DoctorNewPatient />
              </Suspense>
            </RoleProtectedRoute>
          }
        />
        <Route
          path="doctor/pacientes/:id"
          element={
            <RoleProtectedRoute allowedRoles={['doctor']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <DoctorPatientDetails />
              </Suspense>
            </RoleProtectedRoute>
          }
        />
        <Route
          path="doctor/pacientes/:id/editar"
          element={
            <RoleProtectedRoute allowedRoles={['doctor']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <DoctorEditPatient />
              </Suspense>
            </RoleProtectedRoute>
          }
        />
        <Route
          path="doctor/solicitudes"
          element={
            <RoleProtectedRoute allowedRoles={['doctor']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <DoctorRequests />
              </Suspense>
            </RoleProtectedRoute>
          }
        />
        <Route
          path="doctor/todas-solicitudes"
          element={
            <RoleProtectedRoute allowedRoles={['doctor']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <DoctorAllRequests />
              </Suspense>
            </RoleProtectedRoute>
          }
        />
        <Route
          path="doctor/resultados"
          element={
            <RoleProtectedRoute allowedRoles={['doctor']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <DoctorResults />
              </Suspense>
            </RoleProtectedRoute>
          }
        />
        <Route
          path="doctor/resultados/:id/ver"
          element={
            <RoleProtectedRoute allowedRoles={['doctor']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <DoctorResultsView />
              </Suspense>
            </RoleProtectedRoute>
          }
        />
        <Route
          path="doctor/reportes"
          element={
            <RoleProtectedRoute allowedRoles={['doctor']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <DoctorReports />
              </Suspense>
            </RoleProtectedRoute>
          }
        />
        <Route
          path="doctor/solicitudes/nueva"
          element={
            <RoleProtectedRoute allowedRoles={['doctor']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <DoctorNewRequest />
              </Suspense>
            </RoleProtectedRoute>
          }
        />
        <Route
          path="doctor/solicitudes/:id"
          element={
            <RoleProtectedRoute allowedRoles={['doctor']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <DoctorRequestDetail />
              </Suspense>
            </RoleProtectedRoute>
          }
        />
        <Route
          path="doctor/solicitudes/:id/editar"
          element={
            <RoleProtectedRoute allowedRoles={['doctor']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <DoctorEditRequest />
              </Suspense>
            </RoleProtectedRoute>
          }
        />
        <Route
          path="doctor/solicitudes/:id/resultados"
          element={
            <RoleProtectedRoute allowedRoles={['doctor']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <DoctorRequestResults />
              </Suspense>
            </RoleProtectedRoute>
          }
        />
        <Route
          path="doctor/solicitudes/:id/imprimir"
          element={
            <RoleProtectedRoute allowedRoles={['doctor']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <DoctorPrintRequest />
              </Suspense>
            </RoleProtectedRoute>
          }
        />
        <Route
          path="doctor/solicitudes/:id/imprimir-resultados"
          element={
            <RoleProtectedRoute allowedRoles={['doctor']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <DoctorPrintResults />
              </Suspense>
            </RoleProtectedRoute>
          }
        />

        {/* Profile routes - accessible by both roles */}
        <Route
          path="perfil"
          element={
            <RoleProtectedRoute allowedRoles={['doctor', 'laboratorio']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <Profile />
              </Suspense>
            </RoleProtectedRoute>
          }
        />
        <Route
          path="configuracion"
          element={
            <RoleProtectedRoute allowedRoles={['doctor', 'laboratorio']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <Settings />
              </Suspense>
            </RoleProtectedRoute>
          }
        />

        {/* Results view - accessible by both roles */}
        <Route
          path="resultados/:id/ver"
          element={
            <RoleProtectedRoute allowedRoles={['laboratorio']}>
              <Suspense fallback={<LazyLoadingFallback />}>
                <ResultsViewDynamic />
              </Suspense>
            </RoleProtectedRoute>
          }
        />



        {/* 404 route */}
        <Route
          path="*"
          element={
            <Suspense fallback={<LazyLoadingFallback />}>
              <NotFound />
            </Suspense>
          }
        />
      </Route>
          </Routes>
        </WebSocketProvider>
      ) : (
        <Routes>
          {/* Public routes */}
          <Route
            path="/login"
            element={<Login />}
          />
          <Route
            path="/register"
            element={<Register />}
          />
          <Route
            path="*"
            element={<Navigate to="/login" replace />}
          />
        </Routes>
      )}
    </AuthGuard>
  );
}

export default App;
