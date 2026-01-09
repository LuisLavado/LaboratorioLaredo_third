import { Fragment } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { Disclosure, Menu, Transition } from '@headlessui/react';
import { Bars3Icon, XMarkIcon, MoonIcon, SunIcon, BellIcon } from '@heroicons/react/24/outline';
import { useAuth } from '../../contexts/AuthContext';
import { useTheme } from '../../contexts/ThemeContext';
import RealtimeNotifications from '../notifications/RealtimeNotifications';

function classNames(...classes) {
  return classes.filter(Boolean).join(' ');
}

export default function Header() {
  const { user, logout, isDoctor, isLabTechnician } = useAuth();
  const { theme, toggleTheme } = useTheme();
  const location = useLocation();

  // Define navigation items based on user role
  let navigationItems = [];

  if (isDoctor && isDoctor()) {
    // Doctor navigation
    navigationItems = [
      { name: 'Dashboard', href: '/doctor' },
      { name: 'Pacientes', href: '/doctor/pacientes' },
      { name: 'Solicitudes', href: '/doctor/solicitudes' },
      { name: 'Resultados', href: '/doctor/resultados' },
      { name: 'Reportes', href: '/doctor/reportes' },
    ];
  } else if (isLabTechnician && isLabTechnician()) {
    // Lab technician navigation (existing functionality)
    navigationItems = [
      { name: 'Dashboard', href: '/' },
      { name: 'Pacientes', href: '/pacientes' },
      { name: 'Exámenes', href: '/examenes' },
      { name: 'Servicios', href: '/servicios' },
      { name: 'Solicitudes', href: '/solicitudes' },
      { name: 'Resultados', href: '/resultados' },
      { name: 'Reportes', href: '/reportes' },
    ];
  } else {
    // Default navigation
    navigationItems = [
      { name: 'Dashboard', href: '/' },
      { name: 'Pacientes', href: '/pacientes' },
      { name: 'Exámenes', href: '/examenes' },
      { name: 'Servicios', href: '/servicios' },
      { name: 'Solicitudes', href: '/solicitudes' },
      { name: 'Resultados', href: '/resultados' },
      { name: 'Reportes', href: '/reportes' },
    ];
  }

  // Set current based on location with more precise matching
  const navigation = navigationItems.map(item => {
    // For dashboard, only match exact path
    if (item.name === 'Dashboard') {
      return {
        ...item,
        current: location.pathname === item.href
      };
    }

    // For other items, match if path starts with the href but not if it's just a substring
    return {
      ...item,
      current: location.pathname === item.href ||
              (location.pathname.startsWith(`${item.href}/`) &&
               // Make sure we're matching a path segment, not just a substring
               item.href !== '/' &&
               (item.href.endsWith('/') || location.pathname.indexOf(item.href + '/') === 0))
    };
  });

  return (
    <Disclosure as="nav" className="bg-white dark:bg-gray-800 shadow-sm sticky top-0 z-50">
      {({ open }) => (
        <>
          <div className="mx-auto w-4/5 px-4 sm:px-6 lg:px-8">
            <div className="flex h-16 justify-between">
              <div className="flex">
                <div className="flex flex-shrink-0 items-center">
                  <img
                    className="h-8 w-auto"
                    src="/logo.svg"
                    alt="Sistema de Laboratorio"
                  />
                </div>
                <div className="hidden sm:ml-6 sm:flex sm:space-x-8">
                  {navigation.map((item) => (
                    <Link
                      key={item.name}
                      to={item.href}
                      className={classNames(
                        item.current
                          ? 'border-primary-500 text-primary-600 dark:text-primary-400 font-semibold'
                          : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-300 dark:hover:text-white',
                        'inline-flex items-center border-b-2 px-3 pt-1 pb-2 text-sm font-medium transition-colors duration-200 relative'
                      )}
                      aria-current={item.current ? 'page' : undefined}
                    >
                      {item.name}
                      {item.current && (
                        <span className="absolute -bottom-0.5 left-1/2 transform -translate-x-1/2 w-1.5 h-1.5 rounded-full bg-primary-500 dark:bg-primary-400"></span>
                      )}
                    </Link>
                  ))}
                </div>
              </div>
              <div className="hidden sm:ml-6 sm:flex sm:items-center space-x-4">
                {/* Notificaciones en tiempo real */}
                <RealtimeNotifications />

                {/* Botón de cambio de tema */}
                <button
                  type="button"
                  className="rounded-full bg-white dark:bg-gray-800 p-1 text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                  onClick={toggleTheme}
                >
                  <span className="sr-only">Cambiar tema</span>
                  {theme === 'dark' ? (
                    <SunIcon className="h-6 w-6" aria-hidden="true" />
                  ) : (
                    <MoonIcon className="h-6 w-6" aria-hidden="true" />
                  )}
                </button>



                {/* Profile dropdown */}
                <Menu as="div" className="relative ml-3">
                  <div>
                    <Menu.Button className="flex rounded-full bg-white dark:bg-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                      <span className="sr-only">Abrir menú de usuario</span>
                      <div className="h-8 w-8 rounded-full bg-primary-500 flex items-center justify-center text-white">
                        {user?.nombre?.charAt(0) || 'U'}
                      </div>
                    </Menu.Button>
                  </div>
                  <Transition
                    as={Fragment}
                    enter="transition ease-out duration-200"
                    enterFrom="transform opacity-0 scale-95"
                    enterTo="transform opacity-100 scale-100"
                    leave="transition ease-in duration-75"
                    leaveFrom="transform opacity-100 scale-100"
                    leaveTo="transform opacity-0 scale-95"
                  >
                    <Menu.Items className="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white dark:bg-gray-700 py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                      <Menu.Item>
                        {({ active }) => (
                          <Link
                            to="/perfil"
                            className={classNames(
                              active ? 'bg-gray-100 dark:bg-gray-600' : '',
                              'block px-4 py-2 text-sm text-gray-700 dark:text-gray-200'
                            )}
                          >
                            Mi Perfil
                          </Link>
                        )}
                      </Menu.Item>
                      <Menu.Item>
                        {({ active }) => (
                          <Link
                            to="/configuracion"
                            className={classNames(
                              active ? 'bg-gray-100 dark:bg-gray-600' : '',
                              'block px-4 py-2 text-sm text-gray-700 dark:text-gray-200'
                            )}
                          >
                            Configuración
                          </Link>
                        )}
                      </Menu.Item>
                      <Menu.Item>
                        {({ active }) => (
                          <button
                            onClick={logout}
                            className={classNames(
                              active ? 'bg-gray-100 dark:bg-gray-600' : '',
                              'block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-200'
                            )}
                          >
                            Cerrar sesión
                          </button>
                        )}
                      </Menu.Item>
                    </Menu.Items>
                  </Transition>
                </Menu>
              </div>
              <div className="-mr-2 flex items-center sm:hidden">
                {/* Mobile menu button */}
                <Disclosure.Button className="inline-flex items-center justify-center rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 dark:hover:bg-gray-700 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500">
                  <span className="sr-only">Abrir menú principal</span>
                  {open ? (
                    <XMarkIcon className="block h-6 w-6" aria-hidden="true" />
                  ) : (
                    <Bars3Icon className="block h-6 w-6" aria-hidden="true" />
                  )}
                </Disclosure.Button>
              </div>
            </div>
          </div>

          <Disclosure.Panel className="sm:hidden">
            <div className="space-y-1 pb-3 pt-2">
              {navigation.map((item) => (
                <Disclosure.Button
                  key={item.name}
                  as={Link}
                  to={item.href}
                  className={classNames(
                    item.current
                      ? 'bg-primary-50 dark:bg-primary-900/20 border-primary-500 text-primary-700 dark:text-primary-400 font-semibold'
                      : 'border-transparent text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-300 hover:text-gray-700 dark:text-gray-300 dark:hover:text-white',
                    'block border-l-4 py-2 pl-3 pr-4 text-base font-medium transition-colors duration-200'
                  )}
                  aria-current={item.current ? 'page' : undefined}
                >
                  {item.name}
                </Disclosure.Button>
              ))}
            </div>
            <div className="border-t border-gray-200 dark:border-gray-700 pb-3 pt-4">
              <div className="flex items-center px-4">
                <div className="flex-shrink-0">
                  <div className="h-10 w-10 rounded-full bg-primary-500 flex items-center justify-center text-white">
                    {user?.nombre?.charAt(0) || 'U'}
                  </div>
                </div>
                <div className="ml-3">
                  <div className="text-base font-medium text-gray-800 dark:text-white">{user?.nombre} {user?.apellido}</div>
                  <div className="text-sm font-medium text-gray-500 dark:text-gray-400">{user?.email}</div>
                </div>
                <button
                  type="button"
                  className="ml-auto flex-shrink-0 rounded-full bg-white dark:bg-gray-800 p-1 text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                  onClick={toggleTheme}
                >
                  <span className="sr-only">Cambiar tema</span>
                  {theme === 'dark' ? (
                    <SunIcon className="h-6 w-6" aria-hidden="true" />
                  ) : (
                    <MoonIcon className="h-6 w-6" aria-hidden="true" />
                  )}
                </button>
                <button
                  type="button"
                  className="ml-auto flex-shrink-0 rounded-full bg-white dark:bg-gray-800 p-1 text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                >
                  <span className="sr-only">Ver notificaciones</span>
                  <BellIcon className="h-6 w-6" aria-hidden="true" />
                </button>
              </div>
              <div className="mt-3 space-y-1">
                <Disclosure.Button
                  as={Link}
                  to="/perfil"
                  className="block px-4 py-2 text-base font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white"
                >
                  Mi Perfil
                </Disclosure.Button>
                <Disclosure.Button
                  as={Link}
                  to="/configuracion"
                  className="block px-4 py-2 text-base font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white"
                >
                  Configuración
                </Disclosure.Button>
                <Disclosure.Button
                  as="button"
                  onClick={logout}
                  className="block px-4 py-2 text-base font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white w-full text-left"
                >
                  Cerrar sesión
                </Disclosure.Button>
              </div>
            </div>
          </Disclosure.Panel>
        </>
      )}
    </Disclosure>
  );
}
