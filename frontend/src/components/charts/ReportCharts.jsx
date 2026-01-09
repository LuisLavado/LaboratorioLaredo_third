import React, { useState, useEffect } from 'react';
import { useQuery } from '@tanstack/react-query';
import { reportsAPI } from '../../services/api';
import {
  Chart as ChartJS,
  ArcElement,
  Tooltip,
  Legend,
  CategoryScale,
  LinearScale,
  BarElement,
  LineElement,
  PointElement,
  Filler
} from 'chart.js';
import { Doughnut, Bar, Line } from 'react-chartjs-2';
import toast from 'react-hot-toast';

// Registrar componentes de Chart.js
ChartJS.register(
  ArcElement,
  Tooltip,
  Legend,
  CategoryScale,
  LinearScale,
  BarElement,
  LineElement,
  PointElement,
  Filler
);

const ReportCharts = ({ reportType, startDate, endDate, reportsData, doctorId = null }) => {
  const [loading, setLoading] = useState(false);

  // SIMPLIFICADO: Usar directamente los datos de reportes que ya funcionan
  // const { data: chartResponse, isLoading: chartLoading, error: chartError } = useQuery(
  //   ['chart-data', reportType, startDate, endDate, doctorId],
  //   () => reportsAPI.getChartData(reportType, startDate, endDate, doctorId).then(res => res.data),
  //   {
  //     refetchOnWindowFocus: false,
  //     staleTime: 0,
  //     cacheTime: 0,
  //     enabled: !!(startDate && endDate), // Solo ejecutar si tenemos fechas
  //   }
  // );

  // Usar directamente los datos de reportes
  const chartData = reportsData?.data;
  const chartLoading = false;
  const chartError = null;

  // Mostrar loading si los gráficos están cargando
  const isLoading = loading || chartLoading;

  // Preparar datos para gráfico de distribución de estados (doughnut)
  const prepareStatusDistributionData = () => {
    if (!chartData) return null;

    // Usar los datos de estado que ya están en reportsData
    const statusData = [
      { label: 'Pendientes', value: chartData.pendingCount || 0, color: '#F59E0B' },
      { label: 'En Proceso', value: chartData.inProcessCount || 0, color: '#3B82F6' },
      { label: 'Completados', value: chartData.completedCount || 0, color: '#10B981' }
    ];

    console.log('Status data prepared:', statusData);

    // Si no hay datos, mostrar un gráfico con mensaje
    const hasData = statusData.some(item => item.value > 0);
    console.log('Status data hasData:', hasData, statusData);

    if (!hasData) {
      return null;
    }

    // Filtrar solo los que tienen datos
    const filteredData = statusData.filter(item => item.value > 0);

    return {
      labels: filteredData.map(item => item.label),
      datasets: [
        {
          data: filteredData.map(item => item.value),
          backgroundColor: filteredData.map(item => item.color),
          borderColor: '#fff',
          borderWidth: 3,
          hoverBorderWidth: 4,
        },
      ],
    };
  };

  // Preparar datos para gráfico de tendencias diarias (line)
  const prepareDailyTrendsData = () => {
    if (!chartData?.dailyStats || !Array.isArray(chartData.dailyStats) || chartData.dailyStats.length === 0) return null;

    return {
      labels: chartData.dailyStats.map(item => {
        const date = new Date(item.date);
        return date.toLocaleDateString('es-ES', { month: 'short', day: 'numeric' });
      }),
      datasets: [
        {
          label: 'Solicitudes por día',
          data: chartData.dailyStats.map(item => item.count),
          borderColor: 'rgb(59, 130, 246)', // blue-500
          backgroundColor: 'rgba(59, 130, 246, 0.1)',
          borderWidth: 2,
          fill: true,
          tension: 0.4,
          pointBackgroundColor: 'rgb(59, 130, 246)',
          pointBorderColor: 'white',
          pointBorderWidth: 2,
          pointRadius: 4,
          pointHoverRadius: 6,
        },
      ],
    };
  };

  // Preparar datos para gráfico de top exámenes (bar)
  const prepareTopExamsData = () => {
    if (!chartData?.topExams || !Array.isArray(chartData.topExams) || chartData.topExams.length === 0) return null;

    return {
      labels: chartData.topExams.map(item => (item.name && item.name.length > 20) ? item.name.substring(0, 20) + '...' : (item.name || 'Sin nombre')),
      datasets: [
        {
          label: 'Cantidad',
          data: chartData.topExams.map(item => item.count || 0),
          backgroundColor: 'rgba(16, 185, 129, 0.8)', // emerald-500
          borderColor: 'rgb(16, 185, 129)',
          borderWidth: 1,
          borderRadius: 4,
          borderSkipped: false,
        },
      ],
    };
  };

  // Preparar datos para gráfico de top doctores/pacientes (bar)
  const prepareTopItemsData = () => {
    const dataKey = doctorId ? 'topPatients' : 'topDoctors';
    if (!chartData?.[dataKey] || !Array.isArray(chartData[dataKey]) || chartData[dataKey].length === 0) return null;

    const items = chartData[dataKey];
    const label = doctorId ? 'Pacientes' : 'Doctores';

    return {
      labels: items.map(item => {
        const name = (item.name && item.name.length > 15) ? item.name.substring(0, 15) + '...' : (item.name || 'Sin nombre');
        return doctorId && item.dni ? `${name} (${item.dni})` : name;
      }),
      datasets: [
        {
          label: `Top ${label}`,
          data: items.map(item => item.count || 0),
          backgroundColor: 'rgba(245, 158, 11, 0.8)', // amber-500
          borderColor: 'rgb(245, 158, 11)',
          borderWidth: 1,
          borderRadius: 4,
          borderSkipped: false,
        },
      ],
    };
  };

  // Mostrar error si hay problemas con los gráficos
  if (chartError) {
    console.error('Error loading chart data:', chartError);
  }

  // Debug: mostrar información de los datos
  console.log('🔍 ReportCharts Debug COMPLETO:', {
    reportType,
    startDate,
    endDate,
    chartData,
    reportsData,
    isLoading,
    'reportsData.data': reportsData?.data,
    'chartData keys': chartData ? Object.keys(chartData) : 'NO CHARTDATA',
    'reportsData keys': reportsData ? Object.keys(reportsData) : 'NO REPORTSDATA',
    'chartData.examStats': chartData?.examStats,
    'chartData.serviceStats': chartData?.serviceStats,
    'chartData.pendingCount': chartData?.pendingCount
  });

  if (isLoading) {
    return (
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {[1, 2, 3, 4].map((i) => (
          <div key={i} className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div className="animate-pulse">
              <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/3 mb-4"></div>
              <div className="h-64 bg-gray-200 dark:bg-gray-700 rounded"></div>
            </div>
          </div>
        ))}
      </div>
    );
  }

  if (!chartData) {
    return (
      <div className="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-8">
        <div className="text-center">
          <div className="text-yellow-500 text-4xl mb-4">⚠️</div>
          <p className="text-gray-600 dark:text-gray-400 mb-2">No hay datos disponibles para el período indicado</p>
          <p className="text-gray-500 dark:text-gray-500 text-sm">
            Período: {startDate} - {endDate}
          </p>
          <p className="text-gray-500 dark:text-gray-500 text-sm mt-2">
            Intenta seleccionar un rango de fechas diferente
          </p>
        </div>
      </div>
    );
  }

  // Verificar si hay datos válidos - SIMPLIFICADO
  console.log('🔍 VALIDACIÓN DE DATOS DETALLADA:', {
    chartData,
    totalRequests: chartData?.totalRequests,
    serviceStats: chartData?.serviceStats,
    examStats: chartData?.examStats,
    pendingCount: chartData?.pendingCount,
    inProcessCount: chartData?.inProcessCount,
    completedCount: chartData?.completedCount,
    hasChartData: !!chartData,
    chartDataKeys: chartData ? Object.keys(chartData) : 'NO DATA'
  });

  // Solo mostrar mensaje de "no datos" si realmente no hay nada
  if (!chartData) {
    return (
      <div className="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-8">
        <div className="text-center">
          <div className="text-blue-500 text-4xl mb-4">📊</div>
          <p className="text-gray-600 dark:text-gray-400 mb-2">No hay datos disponibles para el período indicado</p>
          <p className="text-gray-500 dark:text-gray-500 text-sm">
            Período: {startDate} - {endDate}
          </p>
          <p className="text-gray-500 dark:text-gray-500 text-sm mt-2">
            Verifica que existan solicitudes en este rango de fechas
          </p>
        </div>
      </div>
    );
  }

  // Configuración de opciones para gráficos circulares con animaciones hermosas
  const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'bottom',
        labels: {
          padding: 20,
          usePointStyle: true,
          pointStyle: 'circle',
          font: {
            size: 12,
            weight: '500'
          }
        }
      },
      tooltip: {
        backgroundColor: 'rgba(0, 0, 0, 0.9)',
        titleColor: '#fff',
        bodyColor: '#fff',
        borderColor: 'rgba(255, 255, 255, 0.1)',
        borderWidth: 1,
        cornerRadius: 12,
        displayColors: true,
        padding: 12,
        titleFont: {
          size: 14,
          weight: 'bold'
        },
        bodyFont: {
          size: 13
        },
        callbacks: {
          label: function(context) {
            const label = context.label || '';
            const value = context.parsed;
            const total = context.dataset.data.reduce((a, b) => a + b, 0);
            const percentage = ((value / total) * 100).toFixed(1);
            return `${label}: ${value} (${percentage}%)`;
          }
        }
      }
    },
    animation: {
      animateRotate: true,
      animateScale: true,
      duration: 1500,
      easing: 'easeInOutQuart'
    },
    hover: {
      animationDuration: 300
    },
    cutout: '60%',
    elements: {
      arc: {
        borderWidth: 3,
        borderColor: '#fff',
        hoverBorderWidth: 4
      }
    }
  };

  // Configuración para gráficos de barras
  const barOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        display: false
      },
      tooltip: {
        backgroundColor: 'rgba(0, 0, 0, 0.9)',
        titleColor: '#fff',
        bodyColor: '#fff',
        borderColor: 'rgba(255, 255, 255, 0.1)',
        borderWidth: 1,
        cornerRadius: 12,
        padding: 12
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        grid: {
          color: 'rgba(156, 163, 175, 0.1)'
        },
        ticks: {
          color: '#6B7280'
        }
      },
      x: {
        grid: {
          display: false
        },
        ticks: {
          color: '#6B7280',
          maxRotation: 45
        }
      }
    },
    animation: {
      duration: 1200,
      easing: 'easeInOutQuart'
    }
  };

  // Configuración para gráficos de línea
  const lineOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        display: false
      },
      tooltip: {
        backgroundColor: 'rgba(0, 0, 0, 0.9)',
        titleColor: '#fff',
        bodyColor: '#fff',
        borderColor: 'rgba(255, 255, 255, 0.1)',
        borderWidth: 1,
        cornerRadius: 12,
        padding: 12
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        grid: {
          color: 'rgba(156, 163, 175, 0.1)'
        },
        ticks: {
          color: '#6B7280'
        }
      },
      x: {
        grid: {
          display: false
        },
        ticks: {
          color: '#6B7280'
        }
      }
    },
    animation: {
      duration: 1500,
      easing: 'easeInOutQuart'
    },
    interaction: {
      intersect: false,
      mode: 'index'
    }
  };

  // Función para renderizar gráficos dinámicos según el tipo de reporte
  const renderDynamicCharts = () => {
    const charts = [];

    console.log('renderDynamicCharts - chartData:', chartData);
    console.log('renderDynamicCharts - reportType:', reportType);

    // Solo mostrar distribución por estado en reportes General y Resultados
    if (reportType === 'general' || reportType === 'results') {
      const statusData = prepareStatusDistributionData();
      console.log('statusData prepared for', reportType, ':', statusData);

      if (statusData && statusData.datasets[0].data.some(val => val > 0)) {
        charts.push(
          <div key="status" className="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-xl transition-all duration-300">
            <h4 className="text-lg font-semibold text-gray-900 dark:text-white mb-6 text-center">
              Distribución por Estado
            </h4>
            <div className="h-80 relative">
              <Doughnut
                data={statusData}
                options={doughnutOptions}
              />
            </div>
          </div>
        );
      }
    }

    // Solo mostrar tendencias diarias en reporte General
    if (reportType === 'general') {
      const trendsData = prepareDailyTrendsData();
      if (trendsData && trendsData.datasets[0].data.length > 0) {
        charts.push(
          <div key="trends" className="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-xl transition-all duration-300">
            <h4 className="text-lg font-semibold text-gray-900 dark:text-white mb-6 text-center">
              Tendencias Diarias
            </h4>
            <div className="h-80">
              <Line
                data={trendsData}
                options={lineOptions}
              />
            </div>
          </div>
        );
      }
    }

    // Gráficos específicos según el tipo de reporte
    if (reportType === 'exams') {
      // Para reporte de exámenes: mostrar distribución de exámenes
      if (chartData.examStats && Array.isArray(chartData.examStats) && chartData.examStats.length > 0) {
        charts.push(
          <div key="exams" className="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-xl transition-all duration-300">
            <h4 className="text-lg font-semibold text-gray-900 dark:text-white mb-6 text-center">
              Distribución de Exámenes
            </h4>
            <div className="h-80 relative">
              <Doughnut
                data={{
                  labels: chartData.examStats.slice(0, 6).map(item =>
                    (item.name && item.name.length > 15) ? item.name.substring(0, 15) + '...' : (item.name || 'Sin nombre')
                  ),
                  datasets: [{
                    data: chartData.examStats.slice(0, 6).map(item => item.count || 0),
                    backgroundColor: [
                      '#10B981', '#3B82F6', '#F59E0B', '#EF4444',
                      '#8B5CF6', '#F97316', '#06B6D4', '#84CC16'
                    ],
                    borderColor: '#fff',
                    borderWidth: 3,
                    hoverBorderWidth: 4
                  }]
                }}
                options={doughnutOptions}
              />
            </div>
          </div>
        );
      }
    } else if (reportType === 'services') {
      // Para reporte de servicios: mostrar distribución de servicios
      if (chartData.serviceStats && Array.isArray(chartData.serviceStats) && chartData.serviceStats.length > 0) {
        charts.push(
          <div key="services" className="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-xl transition-all duration-300">
            <h4 className="text-lg font-semibold text-gray-900 dark:text-white mb-6 text-center">
              Distribución por Servicios
            </h4>
            <div className="h-80 relative">
              <Doughnut
                data={{
                  labels: chartData.serviceStats.slice(0, 6).map(item =>
                    (item.name && item.name.length > 15) ? item.name.substring(0, 15) + '...' : (item.name || 'Sin nombre')
                  ),
                  datasets: [{
                    data: chartData.serviceStats.slice(0, 6).map(item => item.count || 0),
                    backgroundColor: [
                      '#8B5CF6', '#F97316', '#06B6D4', '#84CC16',
                      '#10B981', '#3B82F6', '#F59E0B', '#EF4444'
                    ],
                    borderColor: '#fff',
                    borderWidth: 3,
                    hoverBorderWidth: 4
                  }]
                }}
                options={doughnutOptions}
              />
            </div>
          </div>
        );
      }
    } else if (reportType === 'doctors') {
      // Para reporte de doctores: mostrar distribución de doctores
      if (chartData.doctorStats && Array.isArray(chartData.doctorStats) && chartData.doctorStats.length > 0) {
        charts.push(
          <div key="doctors" className="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-xl transition-all duration-300">
            <h4 className="text-lg font-semibold text-gray-900 dark:text-white mb-6 text-center">
              Solicitudes por Doctor
            </h4>
            <div className="h-80 relative">
              <Doughnut
                data={{
                  labels: chartData.doctorStats.slice(0, 6).map(item =>
                    (item.name && item.name.length > 20) ? item.name.substring(0, 20) + '...' : (item.name || 'Sin nombre')
                  ),
                  datasets: [{
                    data: chartData.doctorStats.slice(0, 6).map(item => item.count || 0),
                    backgroundColor: [
                      '#F59E0B', '#EF4444', '#10B981', '#3B82F6',
                      '#8B5CF6', '#F97316', '#06B6D4', '#84CC16'
                    ],
                    borderColor: '#fff',
                    borderWidth: 3,
                    hoverBorderWidth: 4
                  }]
                }}
                options={doughnutOptions}
              />
            </div>
          </div>
        );
      }
    } else if (reportType === 'patients') {
      // Para reporte de pacientes: mostrar distribución de pacientes
      if (chartData.patientStats && Array.isArray(chartData.patientStats) && chartData.patientStats.length > 0) {
        charts.push(
          <div key="patients" className="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-xl transition-all duration-300">
            <h4 className="text-lg font-semibold text-gray-900 dark:text-white mb-6 text-center">
              Pacientes con Más Solicitudes
            </h4>
            <div className="h-80 relative">
              <Doughnut
                data={{
                  labels: chartData.patientStats.slice(0, 6).map(item =>
                    (item.name && item.name.length > 20) ? item.name.substring(0, 20) + '...' : (item.name || 'Sin nombre')
                  ),
                  datasets: [{
                    data: chartData.patientStats.slice(0, 6).map(item => item.count || 0),
                    backgroundColor: [
                      '#06B6D4', '#84CC16', '#8B5CF6', '#F97316',
                      '#EF4444', '#F59E0B', '#3B82F6', '#10B981'
                    ],
                    borderColor: '#fff',
                    borderWidth: 3,
                    hoverBorderWidth: 4
                  }]
                }}
                options={doughnutOptions}
              />
            </div>
          </div>
        );
      }
    } else if (reportType === 'categories') {
      // Para reporte de categorías: mostrar distribución de categorías
      if (chartData.categoryStats && Array.isArray(chartData.categoryStats) && chartData.categoryStats.length > 0) {
        charts.push(
          <div key="categories" className="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-xl transition-all duration-300">
            <h4 className="text-lg font-semibold text-gray-900 dark:text-white mb-6 text-center">
              Distribución por Categorías
            </h4>
            <div className="h-80 relative">
              <Doughnut
                data={{
                  labels: chartData.categoryStats.slice(0, 6).map(item =>
                    (item.name && item.name.length > 15) ? item.name.substring(0, 15) + '...' : (item.name || 'Sin nombre')
                  ),
                  datasets: [{
                    data: chartData.categoryStats.slice(0, 6).map(item => item.count || 0),
                    backgroundColor: [
                      '#84CC16', '#06B6D4', '#F97316', '#8B5CF6',
                      '#3B82F6', '#10B981', '#EF4444', '#F59E0B'
                    ],
                    borderColor: '#fff',
                    borderWidth: 3,
                    hoverBorderWidth: 4
                  }]
                }}
                options={doughnutOptions}
              />
            </div>
          </div>
        );
      }
    }

    return charts;
  };

  // Calcular cuántos gráficos se van a mostrar para ajustar el grid
  const charts = renderDynamicCharts();
  const chartCount = charts.length;

  // Determinar las clases del grid según la cantidad de gráficos
  const gridClasses = chartCount === 1
    ? "grid grid-cols-1 gap-6 max-w-2xl mx-auto" // 1 gráfico: centrado con ancho máximo
    : "grid grid-cols-1 lg:grid-cols-2 gap-6";    // 2+ gráficos: grid normal

  return (
    <div className={gridClasses}>
      {charts}
    </div>
  );
};

export default ReportCharts;
