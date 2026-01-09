import { Link } from 'react-router-dom';

export default function NotFound() {
  return (
    <div className="min-h-[80vh] flex items-center justify-center py-12">
      <div className="max-w-md w-full space-y-8 text-center">
        <div>
          <h1 className="text-9xl font-extrabold text-primary-600 dark:text-primary-400">404</h1>
          <h2 className="mt-6 text-3xl font-bold text-gray-900 dark:text-white">Página no encontrada</h2>
          <p className="mt-2 text-base text-gray-500 dark:text-gray-400">
            Lo sentimos, no pudimos encontrar la página que estás buscando.
          </p>
        </div>
        <div className="mt-8">
          <Link
            to="/"
            className="inline-flex items-center px-4 py-2 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
          >
            Volver al inicio
          </Link>
        </div>
      </div>
    </div>
  );
}
