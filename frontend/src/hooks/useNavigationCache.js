import { useEffect } from 'react';
import { useLocation } from 'react-router-dom';
import { useQueryClient } from '@tanstack/react-query';

/**
 * Custom hook to handle cache invalidation on navigation
 * Particularly useful for browser back/forward navigation scenarios
 */
export function useNavigationCache(queryKeys = []) {
  const location = useLocation();
  const queryClient = useQueryClient();

  useEffect(() => {
    // Invalidate specified queries when location changes
    // This ensures fresh data when navigating back to a page
    if (queryKeys.length > 0) {
      console.log('Navigation detected - invalidating cache for keys:', queryKeys);
      queryKeys.forEach(key => {
        queryClient.invalidateQueries(key);
      });
    }
  }, [location.pathname, queryClient, queryKeys]);

  return {
    invalidateQueries: (keys) => {
      keys.forEach(key => {
        queryClient.invalidateQueries(key);
      });
    },
    refetchQueries: (keys) => {
      keys.forEach(key => {
        queryClient.refetchQueries(key);
      });
    }
  };
}

export default useNavigationCache;
