import { useEffect } from 'react';

export default function ValueDebugger({ value, fieldName, onChange }) {
  useEffect(() => {
    // Detectar valores problemáticos
    if (value === '-1' || value === -1) {
      console.group('🚨 VALOR PROBLEMÁTICO DETECTADO');
      console.log('Campo:', fieldName);
      console.log('Valor:', value);
      console.log('Tipo:', typeof value);
      console.log('Stack trace:', new Error().stack);
      console.groupEnd();
      
      // Intentar corregir automáticamente
      if (onChange) {
        console.log('🔧 Intentando corregir valor automáticamente...');
        onChange('');
      }
    }
  }, [value, fieldName, onChange]);

  // Solo mostrar en desarrollo
  if (process.env.NODE_ENV !== 'development') {
    return null;
  }

  // Solo mostrar si hay un valor problemático
  if (value !== '-1' && value !== -1) {
    return null;
  }

  return (
    <div style={{
      position: 'fixed',
      top: '10px',
      right: '10px',
      background: 'red',
      color: 'white',
      padding: '10px',
      borderRadius: '5px',
      zIndex: 10000,
      fontSize: '12px',
      maxWidth: '300px'
    }}>
      <strong>⚠️ VALOR PROBLEMÁTICO</strong><br/>
      Campo: {fieldName}<br/>
      Valor: {String(value)}<br/>
      Tipo: {typeof value}
    </div>
  );
}
