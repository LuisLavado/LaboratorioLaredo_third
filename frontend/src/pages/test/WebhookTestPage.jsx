import React from 'react';
import WebhookTest from '../../components/test/WebhookTest';

const WebhookTestPage = () => {
  return (
    <div className="container mx-auto px-4 py-8">
      <div className="mb-6">
        <h1 className="text-3xl font-bold text-gray-900">🧪 Pruebas del Webhook Server</h1>
        <p className="text-gray-600 mt-2">
          Página de pruebas para verificar la conectividad con el servidor de webhooks en AWS EC2
        </p>
        <div className="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
          <h3 className="font-semibold text-blue-800">Información del servidor:</h3>
          <ul className="text-blue-700 text-sm mt-2 space-y-1">
            <li><strong>URL:</strong> http://3.14.3.69:3001/api</li>
            <li><strong>IP Pública:</strong> 3.14.3.69</li>
            <li><strong>Puerto:</strong> 3001</li>
            <li><strong>Protocolo:</strong> HTTP (no HTTPS)</li>
          </ul>
        </div>
      </div>
      
      <WebhookTest />
      
      <div className="mt-8 p-4 bg-gray-50 border border-gray-200 rounded-lg">
        <h3 className="font-semibold text-gray-800 mb-2">🔧 Solución de problemas:</h3>
        <div className="text-sm text-gray-600 space-y-2">
          <p><strong>Si ves errores SSL:</strong> El servidor usa HTTP, no HTTPS</p>
          <p><strong>Si ves errores de red:</strong> Verifica que el puerto 3001 esté abierto en AWS Security Group</p>
          <p><strong>Si ves errores CORS:</strong> El servidor debe permitir el origen http://localhost:5173</p>
          <p><strong>Para verificar manualmente:</strong> Abre http://3.14.3.69:3001/api/health en tu navegador</p>
        </div>
      </div>
    </div>
  );
};

export default WebhookTestPage;
