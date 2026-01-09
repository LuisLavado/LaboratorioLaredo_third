import React, { useState } from 'react';
import { webhookServerAPI } from '../../services/api';
import toast from 'react-hot-toast';

const WebhookTest = () => {
  const [loading, setLoading] = useState(false);
  const [results, setResults] = useState({});

  const testHealth = async () => {
    setLoading(true);
    try {
      const response = await webhookServerAPI.getHealth();
      setResults(prev => ({ ...prev, health: response.data }));
      toast.success('Health check exitoso');
    } catch (error) {
      console.error('Error en health check:', error);
      setResults(prev => ({ ...prev, health: { error: error.message } }));
      toast.error('Error en health check: ' + error.message);
    } finally {
      setLoading(false);
    }
  };

  const testStats = async () => {
    setLoading(true);
    try {
      const response = await webhookServerAPI.getStats();
      setResults(prev => ({ ...prev, stats: response.data }));
      toast.success('Estadísticas obtenidas');
    } catch (error) {
      console.error('Error en stats:', error);
      setResults(prev => ({ ...prev, stats: { error: error.message } }));
      toast.error('Error en stats: ' + error.message);
    } finally {
      setLoading(false);
    }
  };

  const testWebhook = async () => {
    setLoading(true);
    try {
      const response = await webhookServerAPI.testWebhook({
        type: 'solicitud.completed',
        solicitud_id: 1
      });
      setResults(prev => ({ ...prev, webhook: response.data }));
      toast.success('Webhook de prueba enviado');
    } catch (error) {
      console.error('Error en webhook:', error);
      setResults(prev => ({ ...prev, webhook: { error: error.message } }));
      toast.error('Error en webhook: ' + error.message);
    } finally {
      setLoading(false);
    }
  };

  const testDirectFetch = async () => {
    setLoading(true);
    try {
      const response = await fetch('http://3.14.3.69:3001/api/health', {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      });
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      
      const data = await response.json();
      setResults(prev => ({ ...prev, directFetch: data }));
      toast.success('Fetch directo exitoso');
    } catch (error) {
      console.error('Error en fetch directo:', error);
      setResults(prev => ({ ...prev, directFetch: { error: error.message } }));
      toast.error('Error en fetch directo: ' + error.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="p-6 bg-white rounded-lg shadow-md">
      <h2 className="text-2xl font-bold mb-4">🧪 Pruebas del Webhook Server</h2>
      
      <div className="grid grid-cols-2 gap-4 mb-6">
        <button
          onClick={testHealth}
          disabled={loading}
          className="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 disabled:opacity-50"
        >
          {loading ? 'Probando...' : '🔍 Health Check'}
        </button>
        
        <button
          onClick={testStats}
          disabled={loading}
          className="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 disabled:opacity-50"
        >
          {loading ? 'Probando...' : '📊 Estadísticas'}
        </button>
        
        <button
          onClick={testWebhook}
          disabled={loading}
          className="px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600 disabled:opacity-50"
        >
          {loading ? 'Probando...' : '🧪 Test Webhook'}
        </button>
        
        <button
          onClick={testDirectFetch}
          disabled={loading}
          className="px-4 py-2 bg-orange-500 text-white rounded hover:bg-orange-600 disabled:opacity-50"
        >
          {loading ? 'Probando...' : '🔗 Fetch Directo'}
        </button>
      </div>

      <div className="space-y-4">
        <div className="text-sm text-gray-600">
          <strong>URL del servidor:</strong> http://3.14.3.69:3001/api
        </div>
        
        {Object.entries(results).map(([key, result]) => (
          <div key={key} className="border rounded p-3">
            <h3 className="font-semibold mb-2 capitalize">{key}:</h3>
            <pre className="bg-gray-100 p-2 rounded text-xs overflow-auto max-h-40">
              {JSON.stringify(result, null, 2)}
            </pre>
          </div>
        ))}
      </div>
    </div>
  );
};

export default WebhookTest;
