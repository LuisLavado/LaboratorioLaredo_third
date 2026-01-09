import React from 'react';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  LineElement,
  PointElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js';
import { Bar, Line, Doughnut } from 'react-chartjs-2';

// Registrar componentes de Chart.js
ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  LineElement,
  PointElement,
  ArcElement,
  Title,
  Tooltip,
  Legend
);

const ChartContainer = ({ type, data, options, title, className = '' }) => {
  // Configuración base para todos los gráficos
  const baseOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'top',
        labels: {
          usePointStyle: true,
          padding: 20,
          color: 'rgb(107, 114, 128)', // text-gray-500
        },
      },
      title: {
        display: !!title,
        text: title,
        color: 'rgb(55, 65, 81)', // text-gray-700
        font: {
          size: 16,
          weight: 'bold',
        },
        padding: {
          bottom: 20,
        },
      },
      tooltip: {
        backgroundColor: 'rgba(0, 0, 0, 0.8)',
        titleColor: 'white',
        bodyColor: 'white',
        borderColor: 'rgba(255, 255, 255, 0.1)',
        borderWidth: 1,
        cornerRadius: 8,
        padding: 12,
      },
    },
    scales: type !== 'doughnut' ? {
      x: {
        grid: {
          color: 'rgba(107, 114, 128, 0.1)',
        },
        ticks: {
          color: 'rgb(107, 114, 128)',
        },
      },
      y: {
        grid: {
          color: 'rgba(107, 114, 128, 0.1)',
        },
        ticks: {
          color: 'rgb(107, 114, 128)',
        },
        beginAtZero: true,
      },
    } : undefined,
    ...options,
  };

  // Función para renderizar el gráfico según el tipo
  const renderChart = () => {
    switch (type) {
      case 'bar':
        return <Bar data={data} options={baseOptions} />;
      case 'line':
        return <Line data={data} options={baseOptions} />;
      case 'doughnut':
        return <Doughnut data={data} options={baseOptions} />;
      default:
        return <div className="text-gray-500">Tipo de gráfico no soportado</div>;
    }
  };

  return (
    <div className={`bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 ${className}`}>
      <div className="h-80">
        {renderChart()}
      </div>
    </div>
  );
};

export default ChartContainer;
