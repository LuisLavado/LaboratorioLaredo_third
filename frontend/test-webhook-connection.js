// Script para probar la conexión al servidor de webhooks
const axios = require('axios');

const webhookAPI = axios.create({
  baseURL: 'http://3.14.3.69:3001/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'Authorization': 'Bearer lab_webhook_2024_super_secret_key_bonelektroniks',
  },
  timeout: 10000
});

async function testWebhookConnection() {
  console.log('🧪 PROBANDO CONEXIÓN AL SERVIDOR DE WEBHOOKS');
  console.log('==============================================');
  console.log('URL:', 'http://3.14.3.69:3001/api');
  console.log('');

  try {
    // 1. Health check básico
    console.log('1. 🔍 Health check básico...');
    const healthResponse = await webhookAPI.get('/health');
    console.log('✅ Health check exitoso:', healthResponse.data);
    console.log('');

    // 2. Health check detallado
    console.log('2. 🔍 Health check detallado...');
    const detailedHealthResponse = await webhookAPI.get('/health/detailed');
    console.log('✅ Health check detallado exitoso:', detailedHealthResponse.data);
    console.log('');

    // 3. Estadísticas
    console.log('3. 📊 Estadísticas del webhook server...');
    const statsResponse = await webhookAPI.get('/webhooks/stats');
    console.log('✅ Estadísticas obtenidas:', statsResponse.data);
    console.log('');

    // 4. Webhook de prueba
    console.log('4. 🧪 Enviando webhook de prueba...');
    const testWebhookResponse = await webhookAPI.post('/webhooks/test', {
      type: 'solicitud.completed',
      solicitud_id: 1
    });
    console.log('✅ Webhook de prueba exitoso:', testWebhookResponse.data);
    console.log('');

    console.log('🎉 TODAS LAS PRUEBAS EXITOSAS');
    console.log('El servidor de webhooks está funcionando correctamente');
    console.log('');
    console.log('📝 Configuración para el frontend:');
    console.log('  - URL: http://3.14.3.69:3001/api');
    console.log('  - Secret: lab_webhook_2024_super_secret_key_bonelektroniks');
    console.log('  - Estado: ✅ FUNCIONANDO');

  } catch (error) {
    console.error('❌ ERROR EN LA CONEXIÓN:', error.message);
    
    if (error.code === 'ECONNREFUSED') {
      console.error('🔧 POSIBLES SOLUCIONES:');
      console.error('  1. Verificar que el servidor esté corriendo en EC2');
      console.error('  2. Verificar que el puerto 3001 esté abierto en Security Group');
      console.error('  3. Verificar que el firewall permita el puerto 3001');
    } else if (error.response) {
      console.error('📊 Respuesta del servidor:', error.response.status, error.response.data);
    }
  }
}

// Ejecutar la prueba
testWebhookConnection();
