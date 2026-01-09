export default function SkeletonActivity() {
  return (
    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg animate-pulse">
      <div className="px-4 py-5 sm:p-6">
        <div className="h-6 bg-gray-300 dark:bg-gray-700 rounded w-1/3 mb-4"></div>
        <div className="flow-root">
          <ul className="-mb-8 space-y-6">
            {[1, 2, 3].map((i) => (
              <li key={i} className="relative pb-8">
                <div className="relative flex space-x-3">
                  <div className="h-8 w-8 rounded-full bg-gray-300 dark:bg-gray-700"></div>
                  <div className="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                    <div className="flex-1">
                      <div className="h-4 bg-gray-300 dark:bg-gray-700 rounded w-3/4 mb-2"></div>
                      <div className="h-3 bg-gray-300 dark:bg-gray-700 rounded w-1/2"></div>
                    </div>
                    <div className="text-right">
                      <div className="h-3 bg-gray-300 dark:bg-gray-700 rounded w-16"></div>
                    </div>
                  </div>
                </div>
              </li>
            ))}
          </ul>
        </div>
      </div>
    </div>
  );
}
