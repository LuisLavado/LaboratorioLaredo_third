<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Examen;
use App\Models\CampoExamen;

class CamposExamenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar campos existentes para evitar duplicados
        \DB::table('campos_examen')->delete();

        $this->crearCamposHematologia();
        $this->crearCamposBioquimica();
        $this->crearCamposMicrobiologia();
        $this->crearCamposInmunologia();
        $this->crearCamposPerfiles();
        $this->crearCamposAdicionales();
        $this->crearCamposSimples();

        $this->command->info('Campos de exámenes creados exitosamente desde CSV.');
    }

    private function crearCamposHematologia()
    {
        // HEMOGRAMA
        $hemograma = Examen::where('codigo', 'HEM005')->first();
        if ($hemograma) {
            $campos = [
                ['nombre' => 'Hematíes', 'tipo' => 'number', 'unidad' => 'x10^6/uL', 'valor_referencia' => 'H: 4.5-6.0, M: 4.0-5.5', 'seccion' => 'SERIE ROJA'],
                ['nombre' => 'Hemoglobina', 'tipo' => 'number', 'unidad' => 'g/dL', 'valor_referencia' => 'H: 13-17, M: 12-16', 'seccion' => 'SERIE ROJA'],
                ['nombre' => 'Hematocrito', 'tipo' => 'number', 'unidad' => '%', 'valor_referencia' => 'H: 40-53, M: 37-49', 'seccion' => 'SERIE ROJA'],
                ['nombre' => 'VCM', 'tipo' => 'number', 'unidad' => 'fL', 'valor_referencia' => '80-100', 'seccion' => 'ÍNDICES HEMATIMÉTRICOS'],
                ['nombre' => 'HCM', 'tipo' => 'number', 'unidad' => 'pg', 'valor_referencia' => '27-32', 'seccion' => 'ÍNDICES HEMATIMÉTRICOS'],
                ['nombre' => 'CHCM', 'tipo' => 'number', 'unidad' => 'g/dL', 'valor_referencia' => '32-36', 'seccion' => 'ÍNDICES HEMATIMÉTRICOS'],
                ['nombre' => 'Leucocitos', 'tipo' => 'number', 'unidad' => 'x10^3/uL', 'valor_referencia' => '4.0-11.0', 'seccion' => 'SERIE BLANCA'],
                ['nombre' => 'Neutrófilos', 'tipo' => 'number', 'unidad' => '%', 'valor_referencia' => '50-70', 'seccion' => 'FÓRMULA LEUCOCITARIA'],
                ['nombre' => 'Linfocitos', 'tipo' => 'number', 'unidad' => '%', 'valor_referencia' => '20-40', 'seccion' => 'FÓRMULA LEUCOCITARIA'],
                ['nombre' => 'Monocitos', 'tipo' => 'number', 'unidad' => '%', 'valor_referencia' => '2-8', 'seccion' => 'FÓRMULA LEUCOCITARIA'],
                ['nombre' => 'Eosinófilos', 'tipo' => 'number', 'unidad' => '%', 'valor_referencia' => '1-4', 'seccion' => 'FÓRMULA LEUCOCITARIA'],
                ['nombre' => 'Basófilos', 'tipo' => 'number', 'unidad' => '%', 'valor_referencia' => '0-1', 'seccion' => 'FÓRMULA LEUCOCITARIA'],
                ['nombre' => 'Plaquetas', 'tipo' => 'number', 'unidad' => 'x10^3/uL', 'valor_referencia' => '150-450', 'seccion' => 'SERIE PLAQUETARIA'],
            ];
            $this->crearCampos($hemograma->id, $campos);
        }

        // HEMOGLOBINA
        $hemoglobina = Examen::where('codigo', 'HEM004')->first();
        if ($hemoglobina) {
            $campos = [
                ['nombre' => 'Hemoglobina', 'tipo' => 'number', 'unidad' => 'g/dL', 'valor_referencia' => 'H: 13-17, M: 12-16', 'seccion' => 'RESULTADO'],
            ];
            $this->crearCampos($hemoglobina->id, $campos);
        }

        // HEMATOCRITO
        $hematocrito = Examen::where('codigo', 'HEM003')->first();
        if ($hematocrito) {
            $campos = [
                ['nombre' => 'Hematocrito', 'tipo' => 'number', 'unidad' => '%', 'valor_referencia' => 'H: 40-53, M: 37-49', 'seccion' => 'RESULTADO'],
            ];
            $this->crearCampos($hematocrito->id, $campos);
        }

        // TIEMPO DE PROTROMBINA
        $tp = Examen::where('codigo', 'HEM011')->first();
        if ($tp) {
            $campos = [
                ['nombre' => 'Tiempo de Protrombina', 'tipo' => 'number', 'unidad' => 'seg', 'valor_referencia' => '11-13', 'seccion' => 'COAGULACIÓN'],
                ['nombre' => 'INR', 'tipo' => 'number', 'valor_referencia' => '0.8-1.2', 'seccion' => 'COAGULACIÓN'],
                ['nombre' => 'Actividad', 'tipo' => 'number', 'unidad' => '%', 'valor_referencia' => '70-100', 'seccion' => 'COAGULACIÓN'],
            ];
            $this->crearCampos($tp->id, $campos);
        }

        // VSG
        $vsg = Examen::where('codigo', 'HEM014')->first();
        if ($vsg) {
            $campos = [
                ['nombre' => 'VSG', 'tipo' => 'number', 'unidad' => 'mm/h', 'valor_referencia' => 'H: 0-15, M: 0-20', 'seccion' => 'RESULTADO'],
            ];
            $this->crearCampos($vsg->id, $campos);
        }

        // TIEMPO DE SANGRÍA (basado en CSV línea 144)
        $tiempoSangria = Examen::where('codigo', 'HEM012')->first();
        if ($tiempoSangria) {
            $campos = [
                ['nombre' => 'Tiempo de sangría', 'tipo' => 'number', 'unidad' => 'min', 'valor_referencia' => '1 - 4', 'seccion' => 'RESULTADO'],
            ];
            $this->crearCampos($tiempoSangria->id, $campos);
        }

        // TIEMPO DE TROMBOPLASTINA (basado en CSV línea 148)
        $tiempoTromboplastina = Examen::where('codigo', 'HEM013')->first();
        if ($tiempoTromboplastina) {
            $campos = [
                ['nombre' => 'Tiempo Tromboplastina parcial activa', 'tipo' => 'number', 'unidad' => 'seg', 'valor_referencia' => '24.9 - 36.8', 'seccion' => 'RESULTADO'],
            ];
            $this->crearCampos($tiempoTromboplastina->id, $campos);
        }

        // TIEMPO DE COAGULACIÓN (basado en CSV línea 143)
        $tiempoCoagulacion = Examen::where('codigo', 'HEM010')->first();
        if ($tiempoCoagulacion) {
            $campos = [
                ['nombre' => 'Tiempo de coagulación', 'tipo' => 'number', 'unidad' => 'min', 'valor_referencia' => '5 - 15', 'seccion' => 'RESULTADO'],
            ];
            $this->crearCampos($tiempoCoagulacion->id, $campos);
        }
    }

    private function crearCamposBioquimica()
    {
        // GLUCOSA BASAL
        $glucosa = Examen::where('codigo', 'BIO016')->first();
        if ($glucosa) {
            $campos = [
                ['nombre' => 'Glucosa', 'tipo' => 'number', 'unidad' => 'mg/dL', 'valor_referencia' => '70-110', 'seccion' => 'RESULTADO'],
            ];
            $this->crearCampos($glucosa->id, $campos);
        }

        // CREATININA SÉRICA
        $creatinina = Examen::where('codigo', 'BIO010')->first();
        if ($creatinina) {
            $campos = [
                ['nombre' => 'Creatinina', 'tipo' => 'number', 'unidad' => 'mg/dL', 'valor_referencia' => 'H: 0.7-1.3, M: 0.6-1.1', 'seccion' => 'RESULTADO'],
            ];
            $this->crearCampos($creatinina->id, $campos);
        }

        // ÚREA
        $urea = Examen::where('codigo', 'BIO030')->first();
        if ($urea) {
            $campos = [
                ['nombre' => 'Úrea', 'tipo' => 'number', 'unidad' => 'mg/dL', 'valor_referencia' => '15-45', 'seccion' => 'RESULTADO'],
            ];
            $this->crearCampos($urea->id, $campos);
        }

        // ÁCIDO ÚRICO
        $acidoUrico = Examen::where('codigo', 'BIO001')->first();
        if ($acidoUrico) {
            $campos = [
                ['nombre' => 'Ácido Úrico', 'tipo' => 'number', 'unidad' => 'mg/dL', 'valor_referencia' => 'H: 3.5-7.2, M: 2.6-6.0', 'seccion' => 'RESULTADO'],
            ];
            $this->crearCampos($acidoUrico->id, $campos);
        }

        // COLESTEROL TOTAL
        $colesterol = Examen::where('codigo', 'BIO006')->first();
        if ($colesterol) {
            $campos = [
                ['nombre' => 'Colesterol Total', 'tipo' => 'number', 'unidad' => 'mg/dL', 'valor_referencia' => '140-200', 'seccion' => 'RESULTADO'],
            ];
            $this->crearCampos($colesterol->id, $campos);
        }

        // TRIGLICÉRIDOS
        $trigliceridos = Examen::where('codigo', 'BIO029')->first();
        if ($trigliceridos) {
            $campos = [
                ['nombre' => 'Triglicéridos', 'tipo' => 'number', 'unidad' => 'mg/dL', 'valor_referencia' => '40-150', 'seccion' => 'RESULTADO'],
            ];
            $this->crearCampos($trigliceridos->id, $campos);
        }

        // TGO
        $tgo = Examen::where('codigo', 'BIO027')->first();
        if ($tgo) {
            $campos = [
                ['nombre' => 'TGO (AST)', 'tipo' => 'number', 'unidad' => 'U/L', 'valor_referencia' => 'H: 10-40, M: 9-32', 'seccion' => 'RESULTADO'],
            ];
            $this->crearCampos($tgo->id, $campos);
        }

        // TGP
        $tgp = Examen::where('codigo', 'BIO028')->first();
        if ($tgp) {
            $campos = [
                ['nombre' => 'TGP (ALT)', 'tipo' => 'number', 'unidad' => 'U/L', 'valor_referencia' => 'H: 10-55, M: 7-30', 'seccion' => 'RESULTADO'],
            ];
            $this->crearCampos($tgp->id, $campos);
        }

        // BILIRRUBINA TOTAL Y FRACCIONADAS
        $bilirrubina = Examen::where('codigo', 'BIO004')->first();
        if ($bilirrubina) {
            $campos = [
                ['nombre' => 'Bilirrubina Total', 'tipo' => 'number', 'unidad' => 'mg/dL', 'valor_referencia' => '0.2-1.2', 'seccion' => 'RESULTADO'],
                ['nombre' => 'Bilirrubina Directa', 'tipo' => 'number', 'unidad' => 'mg/dL', 'valor_referencia' => '0.0-0.3', 'seccion' => 'RESULTADO'],
                ['nombre' => 'Bilirrubina Indirecta', 'tipo' => 'number', 'unidad' => 'mg/dL', 'valor_referencia' => '0.2-0.9', 'seccion' => 'RESULTADO'],
            ];
            $this->crearCampos($bilirrubina->id, $campos);
        }

        // FOSFATASA ALCALINA
        $fosfatasa = Examen::where('codigo', 'BIO013')->first();
        if ($fosfatasa) {
            $campos = [
                ['nombre' => 'Fosfatasa Alcalina', 'tipo' => 'number', 'unidad' => 'U/L', 'valor_referencia' => '44-147', 'seccion' => 'RESULTADO'],
            ];
            $this->crearCampos($fosfatasa->id, $campos);
        }

        // PROTEÍNAS TOTALES Y FRACCIONADAS
        $proteinas = Examen::where('codigo', 'BIO024')->first();
        if ($proteinas) {
            $campos = [
                ['nombre' => 'Proteínas Totales', 'tipo' => 'number', 'unidad' => 'g/dL', 'valor_referencia' => '6.0-8.3', 'seccion' => 'RESULTADO'],
                ['nombre' => 'Albúmina', 'tipo' => 'number', 'unidad' => 'g/dL', 'valor_referencia' => '3.5-5.0', 'seccion' => 'RESULTADO'],
                ['nombre' => 'Globulinas', 'tipo' => 'number', 'unidad' => 'g/dL', 'valor_referencia' => '2.0-3.5', 'seccion' => 'RESULTADO'],
                ['nombre' => 'Relación A/G', 'tipo' => 'number', 'valor_referencia' => '1.2-2.2', 'seccion' => 'RESULTADO'],
            ];
            $this->crearCampos($proteinas->id, $campos);
        }

        // HEMOGLOBINA GLICOSILADA
        $hba1c = Examen::where('codigo', 'BIO018')->first();
        if ($hba1c) {
            $campos = [
                ['nombre' => 'HbA1c', 'tipo' => 'number', 'unidad' => '%', 'valor_referencia' => '4.0-6.0', 'seccion' => 'RESULTADO'],
                ['nombre' => 'Glucosa Promedio Estimada', 'tipo' => 'number', 'unidad' => 'mg/dL', 'valor_referencia' => '68-126', 'seccion' => 'RESULTADO'],
            ];
            $this->crearCampos($hba1c->id, $campos);
        }
    }

    private function crearCamposMicrobiologia()
    {
        // EXAMEN DE ORINA COMPLETA
        $orinaCompleta = Examen::where('codigo', 'MIC005')->first();
        if ($orinaCompleta) {
            $campos = [
                // ESTUDIO FÍSICO-QUÍMICO
                ['nombre' => 'Color', 'tipo' => 'text', 'valor_referencia' => 'Amarillo claro', 'seccion' => 'ESTUDIO FÍSICO-QUÍMICO'],
                ['nombre' => 'Aspecto', 'tipo' => 'text', 'valor_referencia' => 'Transparente', 'seccion' => 'ESTUDIO FÍSICO-QUÍMICO'],
                ['nombre' => 'Densidad', 'tipo' => 'number', 'unidad' => 'g/L', 'valor_referencia' => '1.000-1.030', 'seccion' => 'ESTUDIO FÍSICO-QUÍMICO'],
                ['nombre' => 'pH', 'tipo' => 'number', 'valor_referencia' => '5.0-9.0', 'seccion' => 'ESTUDIO FÍSICO-QUÍMICO'],
                ['nombre' => 'Proteínas', 'tipo' => 'text', 'valor_referencia' => 'Negativo', 'seccion' => 'ESTUDIO FÍSICO-QUÍMICO'],
                ['nombre' => 'Glucosa', 'tipo' => 'text', 'valor_referencia' => 'Negativo', 'seccion' => 'ESTUDIO FÍSICO-QUÍMICO'],
                ['nombre' => 'Sangre/Hemoglobina', 'tipo' => 'text', 'valor_referencia' => 'Negativo', 'seccion' => 'ESTUDIO FÍSICO-QUÍMICO'],
                ['nombre' => 'Cuerpos Cetónicos', 'tipo' => 'text', 'valor_referencia' => 'Negativo', 'seccion' => 'ESTUDIO FÍSICO-QUÍMICO'],
                ['nombre' => 'Urobilinógeno', 'tipo' => 'text', 'valor_referencia' => 'Normal', 'seccion' => 'ESTUDIO FÍSICO-QUÍMICO'],
                ['nombre' => 'Pigmentos Biliares', 'tipo' => 'text', 'valor_referencia' => 'Negativo', 'seccion' => 'ESTUDIO FÍSICO-QUÍMICO'],
                ['nombre' => 'Nitritos', 'tipo' => 'text', 'valor_referencia' => 'Negativo', 'seccion' => 'ESTUDIO FÍSICO-QUÍMICO'],
                ['nombre' => 'Esterasa Leucocitaria', 'tipo' => 'text', 'valor_referencia' => 'Negativo', 'seccion' => 'ESTUDIO FÍSICO-QUÍMICO'],

                // SEDIMENTO URINARIO
                ['nombre' => 'Hematíes', 'tipo' => 'number', 'unidad' => 'por campo', 'valor_referencia' => '0-4', 'seccion' => 'SEDIMENTO URINARIO'],
                ['nombre' => 'Leucocitos', 'tipo' => 'number', 'unidad' => 'por campo', 'valor_referencia' => '0-5', 'seccion' => 'SEDIMENTO URINARIO'],
                ['nombre' => 'Células Epiteliales', 'tipo' => 'text', 'valor_referencia' => 'Escasas', 'seccion' => 'SEDIMENTO URINARIO'],
                ['nombre' => 'Cilindros', 'tipo' => 'text', 'valor_referencia' => 'No se observan', 'seccion' => 'SEDIMENTO URINARIO'],
                ['nombre' => 'Cristales', 'tipo' => 'text', 'valor_referencia' => 'No se observan', 'seccion' => 'SEDIMENTO URINARIO'],
                ['nombre' => 'Bacterias', 'tipo' => 'text', 'valor_referencia' => 'Escasas', 'seccion' => 'SEDIMENTO URINARIO'],
                ['nombre' => 'Levaduras', 'tipo' => 'text', 'valor_referencia' => 'No se observan', 'seccion' => 'SEDIMENTO URINARIO'],
                ['nombre' => 'Parásitos', 'tipo' => 'text', 'valor_referencia' => 'No se observan', 'seccion' => 'SEDIMENTO URINARIO'],
            ];
            $this->crearCampos($orinaCompleta->id, $campos);
        }

        // EXAMEN DIRECTO DE HECES
        $heces = Examen::where('codigo', 'MIC007')->first();
        if ($heces) {
            $campos = [
                ['nombre' => 'Consistencia', 'tipo' => 'text', 'valor_referencia' => 'Formada', 'seccion' => 'CARACTERÍSTICAS MACROSCÓPICAS'],
                ['nombre' => 'Color', 'tipo' => 'text', 'valor_referencia' => 'Marrón', 'seccion' => 'CARACTERÍSTICAS MACROSCÓPICAS'],
                ['nombre' => 'Moco', 'tipo' => 'text', 'valor_referencia' => 'Ausente', 'seccion' => 'CARACTERÍSTICAS MACROSCÓPICAS'],
                ['nombre' => 'Sangre', 'tipo' => 'text', 'valor_referencia' => 'Ausente', 'seccion' => 'CARACTERÍSTICAS MACROSCÓPICAS'],
                ['nombre' => 'Parásitos', 'tipo' => 'text', 'valor_referencia' => 'No se observan', 'seccion' => 'EXAMEN MICROSCÓPICO'],
                ['nombre' => 'Quistes', 'tipo' => 'text', 'valor_referencia' => 'No se observan', 'seccion' => 'EXAMEN MICROSCÓPICO'],
                ['nombre' => 'Leucocitos', 'tipo' => 'text', 'valor_referencia' => 'Escasos', 'seccion' => 'EXAMEN MICROSCÓPICO'],
                ['nombre' => 'Hematíes', 'tipo' => 'text', 'valor_referencia' => 'No se observan', 'seccion' => 'EXAMEN MICROSCÓPICO'],
            ];
            $this->crearCampos($heces->id, $campos);
        }
    }

    private function crearCamposInmunologia()
    {
        // DIAGNÓSTICO DE EMBARAZO SANGRE
        $embarazoSangre = Examen::where('codigo', 'INM004')->first();
        if ($embarazoSangre) {
            $campos = [
                ['nombre' => 'Beta HCG', 'tipo' => 'text', 'valor_referencia' => 'Negativo', 'seccion' => 'RESULTADO'],
            ];
            $this->crearCampos($embarazoSangre->id, $campos);
        }

        // DIAGNÓSTICO DE EMBARAZO ORINA
        $embarazoOrina = Examen::where('codigo', 'INM003')->first();
        if ($embarazoOrina) {
            $campos = [
                ['nombre' => 'Beta HCG', 'tipo' => 'text', 'valor_referencia' => 'Negativo', 'seccion' => 'RESULTADO'],
            ];
            $this->crearCampos($embarazoOrina->id, $campos);
        }

        // TSH
        $tsh = Examen::where('codigo', 'INM023')->first();
        if ($tsh) {
            $campos = [
                ['nombre' => 'TSH', 'tipo' => 'number', 'unidad' => 'mUI/L', 'valor_referencia' => '0.4-4.0', 'seccion' => 'RESULTADO'],
            ];
            $this->crearCampos($tsh->id, $campos);
        }

        // PROTEÍNA C REACTIVA
        $pcr = Examen::where('codigo', 'INM008')->first();
        if ($pcr) {
            $campos = [
                ['nombre' => 'PCR', 'tipo' => 'number', 'unidad' => 'mg/L', 'valor_referencia' => '<3.0', 'seccion' => 'RESULTADO'],
            ];
            $this->crearCampos($pcr->id, $campos);
        }

        // ANTIESTREPTOLISINA O (ASO)
        $aso = Examen::where('codigo', 'INM001')->first();
        if ($aso) {
            $campos = [
                ['nombre' => 'ASO', 'tipo' => 'number', 'unidad' => 'UI/mL', 'valor_referencia' => '<200', 'seccion' => 'RESULTADO'],
            ];
            $this->crearCampos($aso->id, $campos);
        }

        // FACTOR REUMATOIDEO
        $fr = Examen::where('codigo', 'INM006')->first();
        if ($fr) {
            $campos = [
                ['nombre' => 'Factor Reumatoideo', 'tipo' => 'number', 'unidad' => 'UI/mL', 'valor_referencia' => '<20', 'seccion' => 'RESULTADO'],
            ];
            $this->crearCampos($fr->id, $campos);
        }

        // WIDAL
        $widal = Examen::where('codigo', 'INM016')->first();
        if ($widal) {
            $campos = [
                ['nombre' => 'Salmonella Typhi O', 'tipo' => 'text', 'valor_referencia' => 'Negativo', 'seccion' => 'AGLUTINACIONES'],
                ['nombre' => 'Salmonella Typhi H', 'tipo' => 'text', 'valor_referencia' => 'Negativo', 'seccion' => 'AGLUTINACIONES'],
                ['nombre' => 'Salmonella Paratyphi A', 'tipo' => 'text', 'valor_referencia' => 'Negativo', 'seccion' => 'AGLUTINACIONES'],
                ['nombre' => 'Salmonella Paratyphi B', 'tipo' => 'text', 'valor_referencia' => 'Negativo', 'seccion' => 'AGLUTINACIONES'],
            ];
            $this->crearCampos($widal->id, $campos);
        }

        // RPR
        $rpr = Examen::where('codigo', 'INM018')->first();
        if ($rpr) {
            $campos = [
                ['nombre' => 'RPR', 'tipo' => 'text', 'valor_referencia' => 'No Reactivo', 'seccion' => 'RESULTADO'],
                ['nombre' => 'Título', 'tipo' => 'text', 'valor_referencia' => 'N/A', 'seccion' => 'RESULTADO'],
            ];
            $this->crearCampos($rpr->id, $campos);
        }
    }

    private function crearCamposPerfiles()
    {
        // PERFIL LIPÍDICO
        $perfilLipidico = Examen::where('codigo', 'PER001')->first();
        if ($perfilLipidico) {
            $campos = [
                ['nombre' => 'Colesterol Total', 'tipo' => 'number', 'unidad' => 'mg/dL', 'valor_referencia' => '140-200', 'seccion' => 'PERFIL LIPÍDICO'],
                ['nombre' => 'HDL-Colesterol', 'tipo' => 'number', 'unidad' => 'mg/dL', 'valor_referencia' => 'H: >45, M: >50', 'seccion' => 'PERFIL LIPÍDICO'],
                ['nombre' => 'LDL-Colesterol', 'tipo' => 'number', 'unidad' => 'mg/dL', 'valor_referencia' => '60-129', 'seccion' => 'PERFIL LIPÍDICO'],
                ['nombre' => 'Triglicéridos', 'tipo' => 'number', 'unidad' => 'mg/dL', 'valor_referencia' => '40-150', 'seccion' => 'PERFIL LIPÍDICO'],
                ['nombre' => 'VLDL-Colesterol', 'tipo' => 'number', 'unidad' => 'mg/dL', 'valor_referencia' => '5-30', 'seccion' => 'PERFIL LIPÍDICO'],
            ];
            $this->crearCampos($perfilLipidico->id, $campos);
        }

        // PERFIL RENAL
        $perfilRenal = Examen::where('codigo', 'PER004')->first();
        if ($perfilRenal) {
            $campos = [
                ['nombre' => 'Úrea', 'tipo' => 'number', 'unidad' => 'mg/dL', 'valor_referencia' => '15-45', 'seccion' => 'PERFIL RENAL'],
                ['nombre' => 'Creatinina', 'tipo' => 'number', 'unidad' => 'mg/dL', 'valor_referencia' => 'H: 0.7-1.3, M: 0.6-1.1', 'seccion' => 'PERFIL RENAL'],
                ['nombre' => 'Ácido Úrico', 'tipo' => 'number', 'unidad' => 'mg/dL', 'valor_referencia' => 'H: 3.5-7.2, M: 2.6-6.0', 'seccion' => 'PERFIL RENAL'],
            ];
            $this->crearCampos($perfilRenal->id, $campos);
        }

        // PERFIL HEPÁTICO (basado en CSV líneas 340-353)
        $perfilHepatico = Examen::where('codigo', 'PER003')->first();
        if ($perfilHepatico) {
            $campos = [
                // BILIRRUBINAS
                ['nombre' => 'Bilirrubinas Totales', 'tipo' => 'number', 'unidad' => 'mg/dL', 'valor_referencia' => '0.1 - 1', 'seccion' => 'PERFIL HEPÁTICO'],
                ['nombre' => 'Bilirrubinas Directa', 'tipo' => 'number', 'unidad' => 'mg/dL', 'valor_referencia' => '0 - 0.2', 'seccion' => 'PERFIL HEPÁTICO'],
                ['nombre' => 'Bilirrubinas Indirecta', 'tipo' => 'number', 'unidad' => 'mg/dL', 'valor_referencia' => '0.1 - 0.8', 'seccion' => 'PERFIL HEPÁTICO'],

                // PROTEÍNAS
                ['nombre' => 'Proteínas Totales', 'tipo' => 'number', 'unidad' => 'g/dL', 'valor_referencia' => '6.6 - 8.8', 'seccion' => 'PERFIL HEPÁTICO'],
                ['nombre' => 'Albumina', 'tipo' => 'number', 'unidad' => 'g/dL', 'valor_referencia' => '3.2 - 4.5', 'seccion' => 'PERFIL HEPÁTICO'],
                ['nombre' => 'Globulina', 'tipo' => 'number', 'unidad' => 'g/dL', 'valor_referencia' => '2.6 - 3.1', 'seccion' => 'PERFIL HEPÁTICO'],
                ['nombre' => 'Relación A/G', 'tipo' => 'number', 'valor_referencia' => '1.2 - 2.2', 'seccion' => 'PERFIL HEPÁTICO'],

                // ENZIMAS HEPÁTICAS
                ['nombre' => 'Fosfatasa Alcalina', 'tipo' => 'number', 'unidad' => 'U/L', 'valor_referencia' => 'Niños y Adolescente: ≤ 645', 'seccion' => 'PERFIL HEPÁTICO'],
                ['nombre' => 'Transaminasa (TGO)', 'tipo' => 'number', 'unidad' => 'U/L', 'valor_referencia' => 'H: ≤ 37, M: ≤ 31', 'seccion' => 'PERFIL HEPÁTICO'],
                ['nombre' => 'Transaminasa (TGP)', 'tipo' => 'number', 'unidad' => 'U/L', 'valor_referencia' => 'H: ≤ 42, M: ≤ 32', 'seccion' => 'PERFIL HEPÁTICO'],
                ['nombre' => 'γ-Glutamil transferasa (γ-GT)', 'tipo' => 'number', 'unidad' => 'U/L', 'valor_referencia' => 'H: 8 - 50, M: 8 - 32', 'seccion' => 'PERFIL HEPÁTICO'],
                ['nombre' => 'Lactato Deshidrogenasa (LDH)', 'tipo' => 'number', 'unidad' => 'U/L', 'valor_referencia' => '240 - 479', 'seccion' => 'PERFIL HEPÁTICO'],
                ['nombre' => 'Colinesterasa', 'tipo' => 'number', 'unidad' => 'U/L', 'valor_referencia' => '0', 'seccion' => 'PERFIL HEPÁTICO'],
            ];
            $this->crearCampos($perfilHepatico->id, $campos);
        }

        // PERFIL TIROIDEO (basado en CSV líneas 601-606)
        $perfilTiroideo = Examen::where('codigo', 'PER006')->first();
        if ($perfilTiroideo) {
            $campos = [
                ['nombre' => 'Dosaje de T3 Total', 'tipo' => 'number', 'unidad' => 'ng/mL', 'valor_referencia' => '0.69 - 2.15', 'seccion' => 'PERFIL TIROIDEO'],
                ['nombre' => 'Dosaje de T3 Libre', 'tipo' => 'number', 'unidad' => 'pg/mL', 'valor_referencia' => '2 - 4.2', 'seccion' => 'PERFIL TIROIDEO'],
                ['nombre' => 'Dosaje de T4 Total', 'tipo' => 'number', 'unidad' => 'µg/dL', 'valor_referencia' => '5.2 - 12.7', 'seccion' => 'PERFIL TIROIDEO'],
                ['nombre' => 'Dosaje de T4 Libre', 'tipo' => 'number', 'unidad' => 'ng/dL', 'valor_referencia' => '0.89 - 1.72', 'seccion' => 'PERFIL TIROIDEO'],
                ['nombre' => 'Dosaje de TSH', 'tipo' => 'number', 'unidad' => 'mUI/L', 'valor_referencia' => '0.3 - 4.5', 'seccion' => 'PERFIL TIROIDEO'],
            ];
            $this->crearCampos($perfilTiroideo->id, $campos);
        }

        // PERFIL DE COAGULACIÓN
        $perfilCoagulacion = Examen::where('codigo', 'PER002')->first();
        if ($perfilCoagulacion) {
            $campos = [
                ['nombre' => 'Tiempo de Protrombina', 'tipo' => 'number', 'unidad' => 'seg', 'valor_referencia' => '11-13', 'seccion' => 'PERFIL DE COAGULACIÓN'],
                ['nombre' => 'INR', 'tipo' => 'number', 'valor_referencia' => '0.8-1.2', 'seccion' => 'PERFIL DE COAGULACIÓN'],
                ['nombre' => 'Tiempo de Tromboplastina Parcial', 'tipo' => 'number', 'unidad' => 'seg', 'valor_referencia' => '25-35', 'seccion' => 'PERFIL DE COAGULACIÓN'],
                ['nombre' => 'Tiempo de Coagulación', 'tipo' => 'number', 'unidad' => 'min', 'valor_referencia' => '5-10', 'seccion' => 'PERFIL DE COAGULACIÓN'],
                ['nombre' => 'Tiempo de Sangría', 'tipo' => 'number', 'unidad' => 'min', 'valor_referencia' => '1-3', 'seccion' => 'PERFIL DE COAGULACIÓN'],
            ];
            $this->crearCampos($perfilCoagulacion->id, $campos);
        }

        // PERFIL PRENATAL
        $perfilPrenatal = Examen::where('codigo', 'PER005')->first();
        if ($perfilPrenatal) {
            $campos = [
                ['nombre' => 'Hemoglobina', 'tipo' => 'number', 'unidad' => 'g/dL', 'valor_referencia' => '11.0-15.0', 'seccion' => 'HEMATOLOGÍA'],
                ['nombre' => 'Hematocrito', 'tipo' => 'number', 'unidad' => '%', 'valor_referencia' => '33-45', 'seccion' => 'HEMATOLOGÍA'],
                ['nombre' => 'Grupo Sanguíneo', 'tipo' => 'text', 'valor_referencia' => 'A, B, AB, O', 'seccion' => 'INMUNOHEMATOLOGÍA'],
                ['nombre' => 'Factor Rh', 'tipo' => 'text', 'valor_referencia' => 'Positivo/Negativo', 'seccion' => 'INMUNOHEMATOLOGÍA'],
                ['nombre' => 'Glucosa', 'tipo' => 'number', 'unidad' => 'mg/dL', 'valor_referencia' => '70-110', 'seccion' => 'BIOQUÍMICA'],
                ['nombre' => 'VDRL', 'tipo' => 'text', 'valor_referencia' => 'No Reactivo', 'seccion' => 'SEROLOGÍA'],
                ['nombre' => 'VIH', 'tipo' => 'text', 'valor_referencia' => 'No Reactivo', 'seccion' => 'SEROLOGÍA'],
                ['nombre' => 'Hepatitis B (HBsAg)', 'tipo' => 'text', 'valor_referencia' => 'No Reactivo', 'seccion' => 'SEROLOGÍA'],
                ['nombre' => 'Examen de Orina', 'tipo' => 'text', 'valor_referencia' => 'Normal', 'seccion' => 'UROANÁLISIS'],
            ];
            $this->crearCampos($perfilPrenatal->id, $campos);
        }
    }

    private function crearCampos($examenId, $campos)
    {
        foreach ($campos as $index => $campo) {
            CampoExamen::firstOrCreate([
                'examen_id' => $examenId,
                'nombre' => $campo['nombre'],
            ], [
                'tipo' => $campo['tipo'],
                'unidad' => $campo['unidad'] ?? null,
                'valor_referencia' => $campo['valor_referencia'] ?? null,
                'requerido' => true,
                'orden' => $index + 1,
                'seccion' => $campo['seccion'],
                'activo' => true,
                'version' => 1
            ]);
        }
    }

    private function crearCamposAdicionales()
    {
        // ELECTROLITOS (basado en CSV líneas 356-363)
        $electrolitos = Examen::where('nombre', 'like', '%ELECTROLITO%')->first();
        if ($electrolitos) {
            $campos = [
                ['nombre' => 'Calcio (Ca)', 'tipo' => 'number', 'unidad' => 'mg/dL', 'valor_referencia' => '9.2 - 11.0', 'seccion' => 'ELECTROLITOS'],
                ['nombre' => 'Calcio Iónico (Ca)', 'tipo' => 'number', 'unidad' => 'mg/dL', 'valor_referencia' => '0', 'seccion' => 'ELECTROLITOS'],
                ['nombre' => 'Cloro', 'tipo' => 'number', 'valor_referencia' => '96 - 108', 'seccion' => 'ELECTROLITOS'],
                ['nombre' => 'Fosforo', 'tipo' => 'number', 'unidad' => 'mg/dL', 'valor_referencia' => '2.7 - 4.5', 'seccion' => 'ELECTROLITOS'],
                ['nombre' => 'Potasio', 'tipo' => 'number', 'valor_referencia' => '0', 'seccion' => 'ELECTROLITOS'],
                ['nombre' => 'Sodio (Na)', 'tipo' => 'number', 'unidad' => 'mg/dL', 'valor_referencia' => '135 - 155', 'seccion' => 'ELECTROLITOS'],
                ['nombre' => 'Magnesio', 'tipo' => 'number', 'valor_referencia' => '0', 'seccion' => 'ELECTROLITOS'],
            ];
            $this->crearCampos($electrolitos->id, $campos);
        }

        // TOLERANCIA A LA GLUCOSA (basado en CSV líneas 276-282)
        $toleranciaGlucosa = Examen::where('nombre', 'like', '%TOLERANCIA%GLUCOSA%')->first();
        if ($toleranciaGlucosa) {
            $campos = [
                ['nombre' => 'Glucosa', 'tipo' => 'number', 'unidad' => 'mg/dL', 'valor_referencia' => '70 - 100*', 'seccion' => 'TOLERANCIA A LA GLUCOSA'],
                ['nombre' => 'Glucosa 1 hora', 'tipo' => 'number', 'unidad' => 'mg/dL', 'valor_referencia' => '120 - 170', 'seccion' => 'TOLERANCIA A LA GLUCOSA'],
                ['nombre' => 'Glucosa 2 hora', 'tipo' => 'number', 'unidad' => 'mg/dL', 'valor_referencia' => '70 - 139*', 'seccion' => 'TOLERANCIA A LA GLUCOSA'],
                ['nombre' => 'Glucosa post prandial', 'tipo' => 'number', 'unidad' => 'mg/dL', 'valor_referencia' => 'No diabetico: < 140, Diabetico: < 180*', 'seccion' => 'TOLERANCIA A LA GLUCOSA'],
            ];
            $this->crearCampos($toleranciaGlucosa->id, $campos);
        }
    }

    private function crearCamposSimples()
    {
        // Para exámenes que solo necesitan un campo simple
        $examenesSimples = [
            // Microbiología
            'MIC014' => ['nombre' => 'Sangre oculta en materia fecal', 'tipo' => 'text', 'valor_referencia' => 'Negativo', 'unidad' => null],

            // Hematología
            'HEM001' => ['nombre' => 'Resultado', 'tipo' => 'text', 'valor_referencia' => 'Según criterio médico', 'unidad' => null],

            // Otros exámenes individuales que podrían necesitar campos simples
            // Se pueden agregar más según necesidad
        ];

        foreach ($examenesSimples as $codigo => $campoData) {
            $examen = Examen::where('codigo', $codigo)->first();
            if ($examen) {
                $campos = [
                    [
                        'nombre' => $campoData['nombre'],
                        'tipo' => $campoData['tipo'],
                        'unidad' => $campoData['unidad'],
                        'valor_referencia' => $campoData['valor_referencia'],
                        'seccion' => 'RESULTADO'
                    ]
                ];
                $this->crearCampos($examen->id, $campos);
            }
        }
    }
}
