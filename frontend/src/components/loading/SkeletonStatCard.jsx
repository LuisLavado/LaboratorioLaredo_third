export default function SkeletonStatCard() {
  return (
    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 animate-pulse">
      <div className="flex justify-between items-start">
        <div className="flex-1">
          <div className="h-3 bg-gray-300 dark:bg-gray-700 rounded w-1/2 mb-3"></div>
          <div className="h-8 bg-gray-300 dark:bg-gray-700 rounded w-1/3 mb-3"></div>
          <div className="h-3 bg-gray-300 dark:bg-gray-700 rounded w-2/3"></div>
        </div>
        <div className="w-14 h-14 bg-gray-300 dark:bg-gray-700 rounded-lg flex-shrink-0"></div>
      </div>
    </div>
  );
}
