<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Categoria;
use App\Models\Examen;

class ExamenesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Verificar que existan categorías, si no, crearlas
        $this->createCategorias();
        
        // Obtener las categorías para asignar sus IDs correctamente
        $categorias = Categoria::all()->keyBy('nombre');
        
        $examenes = [
            // ÁREA DE HEMATOLOGÍA
            ['codigo' => 'HEM001', 'nombre' => 'CONSTANTES CORPUSCULARES', 'categoria_id' => $categorias['HEMATOLOGIA']->id],
            ['codigo' => 'HEM002', 'nombre' => 'GRUPO SANGUÍNEO Y FACTOR RH', 'categoria_id' => $categorias['HEMATOLOGIA']->id],
            ['codigo' => 'HEM003', 'nombre' => 'HEMATOCRITO', 'categoria_id' => $categorias['HEMATOLOGIA']->id],
            ['codigo' => 'HEM004', 'nombre' => 'HEMOGLOBINA', 'categoria_id' => $categorias['HEMATOLOGIA']->id],
            ['codigo' => 'HEM005', 'nombre' => 'HEMOGRAMA', 'categoria_id' => $categorias['HEMATOLOGIA']->id],
            ['codigo' => 'HEM006', 'nombre' => 'HEMOGRAMA AUTOMATIZADO', 'categoria_id' => $categorias['HEMATOLOGIA']->id],
            ['codigo' => 'HEM007', 'nombre' => 'HEMOGRAMA COMPLETO (HM+HB+HTO)', 'categoria_id' => $categorias['HEMATOLOGIA']->id],
            ['codigo' => 'HEM008', 'nombre' => 'LAMINA PERIFÉRICA', 'categoria_id' => $categorias['HEMATOLOGIA']->id],
            ['codigo' => 'HEM009', 'nombre' => 'RECUENTO DE PLAQUETAS', 'categoria_id' => $categorias['HEMATOLOGIA']->id],
            ['codigo' => 'HEM010', 'nombre' => 'TIEMPO DE COAGULACIÓN', 'categoria_id' => $categorias['HEMATOLOGIA']->id],
            ['codigo' => 'HEM011', 'nombre' => 'TIEMPO DE PROTROMBINA', 'categoria_id' => $categorias['HEMATOLOGIA']->id],
            ['codigo' => 'HEM012', 'nombre' => 'TIEMPO DE SANGRÍA', 'categoria_id' => $categorias['HEMATOLOGIA']->id],
            ['codigo' => 'HEM013', 'nombre' => 'TIEMPO DE TROMBOPLASTINA', 'categoria_id' => $categorias['HEMATOLOGIA']->id],
            ['codigo' => 'HEM014', 'nombre' => 'VELOCIDAD DE SEDIMENTACIÓN GLOBULAR (VSG)', 'categoria_id' => $categorias['HEMATOLOGIA']->id],
            ['codigo' => 'HEM015', 'nombre' => 'RECUENTO DE RETICULOCITOS', 'categoria_id' => $categorias['HEMATOLOGIA']->id],

            // ÁREA DE BIOQUÍMICA
            ['codigo' => 'BIO001', 'nombre' => 'ÁCIDO ÚRICO', 'categoria_id' => $categorias['BIOQUIMICA']->id],
            ['codigo' => 'BIO002', 'nombre' => 'ALBUMINA', 'categoria_id' => $categorias['BIOQUIMICA']->id],
            ['codigo' => 'BIO003', 'nombre' => 'AMILASA SÉRICA', 'categoria_id' => $categorias['BIOQUIMICA']->id],
            ['codigo' => 'BIO004', 'nombre' => 'BILIRRUBINA TOTAL Y FRACCIONADAS', 'categoria_id' => $categorias['BIOQUIMICA']->id],
            ['codigo' => 'BIO005', 'nombre' => 'COLESTEROL-LDL', 'categoria_id' => $categorias['BIOQUIMICA']->id],
            ['codigo' => 'BIO006', 'nombre' => 'COLESTEROL TOTAL', 'categoria_id' => $categorias['BIOQUIMICA']->id],
            ['codigo' => 'BIO007', 'nombre' => 'COLESTEROL-HDL', 'categoria_id' => $categorias['BIOQUIMICA']->id],
            ['codigo' => 'BIO008', 'nombre' => 'COLINESTERASA', 'categoria_id' => $categorias['BIOQUIMICA']->id],
            ['codigo' => 'BIO009', 'nombre' => 'CREATININA EN ORINA', 'categoria_id' => $categorias['BIOQUIMICA']->id],
            ['codigo' => 'BIO010', 'nombre' => 'CREATININA SÉRICA', 'categoria_id' => $categorias['BIOQUIMICA']->id],
            ['codigo' => 'BIO011', 'nombre' => 'DEPURACIÓN DE CREATININA', 'categoria_id' => $categorias['BIOQUIMICA']->id],
            ['codigo' => 'BIO012', 'nombre' => 'FOSFATASA ACIDA', 'categoria_id' => $categorias['BIOQUIMICA']->id],
            ['codigo' => 'BIO013', 'nombre' => 'FOSFATASA ALCALINA', 'categoria_id' => $categorias['BIOQUIMICA']->id],
            ['codigo' => 'BIO014', 'nombre' => 'FERRITINA', 'categoria_id' => $categorias['BIOQUIMICA']->id],
            ['codigo' => 'BIO015', 'nombre' => 'GAMMAGLUTAMILTRANSPEPTIDASA', 'categoria_id' => $categorias['BIOQUIMICA']->id],
            ['codigo' => 'BIO016', 'nombre' => 'GLUCOSA BASAL', 'categoria_id' => $categorias['BIOQUIMICA']->id],
            ['codigo' => 'BIO017', 'nombre' => 'GLUCOSA POSTPRANDIAL', 'categoria_id' => $categorias['BIOQUIMICA']->id],
            ['codigo' => 'BIO018', 'nombre' => 'HEMOGLOBINA GLICOSILADA', 'categoria_id' => $categorias['BIOQUIMICA']->id],
            ['codigo' => 'BIO019', 'nombre' => 'MICROALBUMINURIA', 'categoria_id' => $categorias['BIOQUIMICA']->id],
            ['codigo' => 'BIO020', 'nombre' => 'LACTATO DESHIDROGENASA LACTICA (LDH)', 'categoria_id' => $categorias['BIOQUIMICA']->id],
            ['codigo' => 'BIO021', 'nombre' => 'LIPASA', 'categoria_id' => $categorias['BIOQUIMICA']->id],
            ['codigo' => 'BIO022', 'nombre' => 'PROTEÍNAS EN ORINA', 'categoria_id' => $categorias['BIOQUIMICA']->id],
            ['codigo' => 'BIO023', 'nombre' => 'PROTEÍNAS EN ORINA DE 24 HORAS', 'categoria_id' => $categorias['BIOQUIMICA']->id],
            ['codigo' => 'BIO024', 'nombre' => 'PROTEÍNAS TOTALES Y FRACCIONADAS', 'categoria_id' => $categorias['BIOQUIMICA']->id],
            ['codigo' => 'BIO025', 'nombre' => 'TEST DE ÁCIDO SULFOSALICILICO (ASS)', 'categoria_id' => $categorias['BIOQUIMICA']->id],
            ['codigo' => 'BIO026', 'nombre' => 'TOLERANCIA A LA GLUCOSA', 'categoria_id' => $categorias['BIOQUIMICA']->id],
            ['codigo' => 'BIO027', 'nombre' => 'TRANSAMINASA TGO', 'categoria_id' => $categorias['BIOQUIMICA']->id],
            ['codigo' => 'BIO028', 'nombre' => 'TRANSAMINASA TGP', 'categoria_id' => $categorias['BIOQUIMICA']->id],
            ['codigo' => 'BIO029', 'nombre' => 'TRIGLICERIDOS', 'categoria_id' => $categorias['BIOQUIMICA']->id],
            ['codigo' => 'BIO030', 'nombre' => 'ÚREA', 'categoria_id' => $categorias['BIOQUIMICA']->id],

            // ÁREA INMUNOLOGÍA
            ['codigo' => 'INM001', 'nombre' => 'ANTIESTREPTOLISINA O CUANTITATIVO (ASO)', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM002', 'nombre' => 'ANTIESTREPTOLISINA O CUALITATIVO', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM003', 'nombre' => 'DIAGNÓSTICO DE EMBARAZO ORINA', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM004', 'nombre' => 'DIAGNÓSTICO DE EMBARAZO SANGRE', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM005', 'nombre' => 'FACTOR REUMATOIDEO CUALITATIVO', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM006', 'nombre' => 'FACTOR REUMATOIDEO CUANTITATIVO', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM007', 'nombre' => 'PROTEÍNAS C-REACTIVA CUALITATIVO', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM008', 'nombre' => 'PROTEÍNAS C-REACTIVA CUANTITATIVO', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM009', 'nombre' => 'PRUEBA HCG CUANTITATIVO ELISA', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM010', 'nombre' => 'PRUEBA DE PSA TOTAL ELISA', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM011', 'nombre' => 'PRUEBA DE ROSA DE BENGALA', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM012', 'nombre' => 'PRUEBA RÁPIDA DE DENGUE', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM013', 'nombre' => 'PRUEBA RÁPIDA DE HEPATITIS A', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM014', 'nombre' => 'PRUEBA RÁPIDA DE HEPATITIS B', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM015', 'nombre' => 'PRUEBA RÁPIDA DE PREGNOSTICON', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM016', 'nombre' => 'REACCIÓN DE WIDAL', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM017', 'nombre' => 'RPR CUALITATIVO', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM018', 'nombre' => 'RPR CUANTITATIVO', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM019', 'nombre' => 'DOSAJE DE T3 TOTAL (ELISA)', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM020', 'nombre' => 'DOSAJE DE T3 LIBRE (ELISA)', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM021', 'nombre' => 'DOSAJE DE T4 TOTAL (ELISA)', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM022', 'nombre' => 'DOSAJE DE T4 LIBRE (ELISA)', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM023', 'nombre' => 'DOSAJE DE TSH (ELISA)', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM024', 'nombre' => 'PROLACTINA (ELISA)', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM025', 'nombre' => 'HORMONA LUTEINIZANTE (LH) ELISA', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM026', 'nombre' => 'HORMONA FOLICULO ESTIMULANTE (FSH) ELISA', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM027', 'nombre' => 'ADENOSINA DESAMINASA (ADA)', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM028', 'nombre' => 'ANTIGENO PROSTATICO TOTAL (QUIMIOLUMINISCENCIA)', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM029', 'nombre' => 'ANTIGENO PROSTATICO LIBRE (QUIMIOLUMINISCENCIA)', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM030', 'nombre' => 'INSULINA (QUIMIOLUMINISCENCIA)', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM031', 'nombre' => 'HORMONA TIROIDEA ESTIMULANTE (TSH) (QUIMIOLUMINISCENCIA)', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM032', 'nombre' => 'HORMONA TRIYODOTIRONINA (T3) TOTAL (QUIMIOLUMINISCENCIA)', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM033', 'nombre' => 'HORMONA TRIYODOTIRONINA(T3) LIBRE (QUIMIOLUMINISCENCIA)', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM034', 'nombre' => 'HORMONA TIROXINA (T4) TOTAL (QUIMIOLUMINISCENCIA)', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM035', 'nombre' => 'HORMONA TIROXINA (T4) LIBRE (QUIMIOLUMINISCENCIA)', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM036', 'nombre' => 'ALFA FETOPROTEINA (AFP) (QUIMIOLUMINISCENCIA)', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM037', 'nombre' => 'PROTEINA C REACTIVA ULTRASENSIBLE (QUIMIOLUMINISCENCIA)', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM038', 'nombre' => 'ANTI HEPATITIS C (QUIMIOLUMINISCENCIA)', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM039', 'nombre' => 'CIANOCOBALAMINA (VITAMINA B12) (QUIMIOLUMINISCENCIA)', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM040', 'nombre' => 'ANTIGENO CARCINOEMBRIONARIO (CEA) (QUIMIOLUMINISCENCIA)', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM041', 'nombre' => 'CITOMEGALOVIRUS ANTICUERPO IgM (QUIMIOLUMINISCENCIA)', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM042', 'nombre' => 'TOXOPLASMA GONDII IgM (QUIMIOLUMINISCENCIA)', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM043', 'nombre' => 'REACTIVO PARA HERPES VIRUS 1-2 IgG (QUIMIOLUMINISCENCIA)', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM044', 'nombre' => 'REACTIVO PARA HERPES VIRUS 1-2 IgM (QUIMIOLUMINISCENCIA)', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM045', 'nombre' => 'HEPATITIS B-Ag SUPERFICIE (QUIMIOLUMINISCENCIA)', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM046', 'nombre' => 'ANTICUERPO ANTIDENGUE NS1 ELISA CAPTURA', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM047', 'nombre' => 'ANTICUERPO ANTIDENGUE IGG ELISA CAPTURA', 'categoria_id' => $categorias['INMUNOLOGIA']->id],
            ['codigo' => 'INM048', 'nombre' => 'PRUEBAS RAPIDAS SAR-COV 2', 'categoria_id' => $categorias['INMUNOLOGIA']->id],

            // ÁREA MICROBIOLOGÍA
            ['codigo' => 'MIC001', 'nombre' => 'CULTIVO SECRECIONES Y ANTIBIOGRAMA', 'categoria_id' => $categorias['MICROBIOLOGIA']->id],
            ['codigo' => 'MIC002', 'nombre' => 'COLORACIÓN GRAM', 'categoria_id' => $categorias['MICROBIOLOGIA']->id],
            ['codigo' => 'MIC003', 'nombre' => 'COPROCULTIVO Y ANTIBIOGRAMA', 'categoria_id' => $categorias['MICROBIOLOGIA']->id],
            ['codigo' => 'MIC004', 'nombre' => 'CULTIVO DE LÍQUIDOS BIOLÓGICOS', 'categoria_id' => $categorias['MICROBIOLOGIA']->id],
            ['codigo' => 'MIC005', 'nombre' => 'EXAMEN DE ORINA COMPLETA', 'categoria_id' => $categorias['MICROBIOLOGIA']->id],
            ['codigo' => 'MIC006', 'nombre' => 'EXAMEN DE ORINA COMPLETA Y GRAM S/C', 'categoria_id' => $categorias['MICROBIOLOGIA']->id],
            ['codigo' => 'MIC007', 'nombre' => 'EXAMEN DIRECTO DE HECES (1 MUESTRA)', 'categoria_id' => $categorias['MICROBIOLOGIA']->id],
            ['codigo' => 'MIC008', 'nombre' => 'EXAMEN DIRECTO DE RASPADO DE PIEL(HONGOS Y ÁCAROS)', 'categoria_id' => $categorias['MICROBIOLOGIA']->id],
            ['codigo' => 'MIC009', 'nombre' => 'EXAMEN DE SECRECIONES', 'categoria_id' => $categorias['MICROBIOLOGIA']->id],
            ['codigo' => 'MIC010', 'nombre' => 'EXAMEN SERIADO DE HECES (3 MUESTRAS)', 'categoria_id' => $categorias['MICROBIOLOGIA']->id],
            ['codigo' => 'MIC011', 'nombre' => 'LEUCOCITOS EN MOCO FECAL', 'categoria_id' => $categorias['MICROBIOLOGIA']->id],
            ['codigo' => 'MIC012', 'nombre' => 'MÉTODO DE CONCENTRACIÓN DE HECES', 'categoria_id' => $categorias['MICROBIOLOGIA']->id],
            ['codigo' => 'MIC013', 'nombre' => 'PRUEBA DE HELECHO', 'categoria_id' => $categorias['MICROBIOLOGIA']->id],
            ['codigo' => 'MIC014', 'nombre' => 'PRUEBA RÁPIDA DE SANGRE OCULTA EN HECES', 'categoria_id' => $categorias['MICROBIOLOGIA']->id],
            ['codigo' => 'MIC015', 'nombre' => 'RECUENTO DIFERENCIAL EN HECES', 'categoria_id' => $categorias['MICROBIOLOGIA']->id],
            ['codigo' => 'MIC016', 'nombre' => 'TEST DE GRAHAM (PARCHE)', 'categoria_id' => $categorias['MICROBIOLOGIA']->id],
            ['codigo' => 'MIC017', 'nombre' => 'UROCULTIVO Y ANTIBIOGRAMA', 'categoria_id' => $categorias['MICROBIOLOGIA']->id],
            ['codigo' => 'MIC018', 'nombre' => 'HEMOCULTIVOS', 'categoria_id' => $categorias['MICROBIOLOGIA']->id],
            ['codigo' => 'MIC019', 'nombre' => 'CULTIVO DE HONGOS', 'categoria_id' => $categorias['MICROBIOLOGIA']->id],

            // PERFILES
            ['codigo' => 'PER001', 'nombre' => 'PERFIL LIPÍDICO', 'categoria_id' => $categorias['PERFILES']->id],
            ['codigo' => 'PER002', 'nombre' => 'PERFIL DE COAGULACIÓN', 'categoria_id' => $categorias['PERFILES']->id],
            ['codigo' => 'PER003', 'nombre' => 'PERFIL HEPÁTICO', 'categoria_id' => $categorias['PERFILES']->id],
            ['codigo' => 'PER004', 'nombre' => 'PERFIL RENAL', 'categoria_id' => $categorias['PERFILES']->id],
            ['codigo' => 'PER005', 'nombre' => 'PERFIL PRENATAL', 'categoria_id' => $categorias['PERFILES']->id],
            ['codigo' => 'PER006', 'nombre' => 'PERFIL TIROIDEO', 'categoria_id' => $categorias['PERFILES']->id],
        ];

        foreach ($examenes as $examen) {
            Examen::firstOrCreate(
                ['codigo' => $examen['codigo']], // Buscar por código
                $examen // Si no existe, crear con todos los datos
            );
        }
    }
    
    private function createCategorias()
    {
        // Verificar si ya existen las categorías
        if (Categoria::count() == 0) {
            // Crear las categorías según el documento oficial del Hospital Distrital Laredo
            $categorias = [
                ['nombre' => 'HEMATOLOGIA'],
                ['nombre' => 'BIOQUIMICA'],
                ['nombre' => 'INMUNOLOGIA'],
                ['nombre' => 'MICROBIOLOGIA'],
                ['nombre' => 'PERFILES'],
            ];

            foreach ($categorias as $categoria) {
                Categoria::firstOrCreate(['nombre' => $categoria['nombre']]);
            }
        }
    }
}