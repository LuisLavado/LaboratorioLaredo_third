import { useParams, Link } from 'react-router-dom';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';

export default function ResultDetails() {
  const { id } = useParams();
  
  return (
    <div>
      <div className="mb-6">
        <div className="flex items-center">
          <Link
            to="/resultados"
            className="mr-4 inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
          >
            <ArrowLeftIcon className="h-5 w-5" aria-hidden="true" />
          </Link>
          <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">
            Registro de Resultados
          </h1>
        </div>
      </div>
      
      <div className="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:p-6">
          <p className="text-center text-gray-500 dark:text-gray-400">
            Formulario para registrar resultados de la solicitud con ID: {id}
          </p>
          <p className="text-center text-gray-500 dark:text-gray-400 mt-2">
            (Componente en desarrollo)
          </p>
        </div>
      </div>
    </div>
  );
}
