<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Policy;
use App\Models\Customer;
use App\Models\CompanyMaster;
use App\Models\InsurancePolicyType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ImportPolicyPdfs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'policy:import-pdfs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyzes all PDFs in demo pdfs and imports/fills the policies into the database.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pdfDir = base_path('demo pdfs');
        if (!is_dir($pdfDir)) {
            $this->error("Directory not found: {$pdfDir}");
            return 1;
        }

        $files = glob($pdfDir . DIRECTORY_SEPARATOR . '*.pdf');
        if (empty($files)) {
            $this->warn("No PDF files found in: {$pdfDir}");
            return 0;
        }

        $this->info("Found " . count($files) . " PDF files to import.");
        
        $successCount = 0;
        $totalCount = count($files);

        foreach ($files as $filepath) {
            $filename = basename($filepath);
            $this->info("--------------------------------------------------");
            $this->info("Processing: {$filename}");

            // Run python parser
            $pythonScript = app_path('Services/pdf_parser.py');
            $command = 'python ' . escapeshellarg($pythonScript) . ' ' . escapeshellarg($filepath);
            
            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                $this->error("Failed to run python parser on: {$filename}");
                continue;
            }

            $jsonStr = implode("\n", $output);
            // Locate JSON body if warnings are printed
            $jsonStart = strpos($jsonStr, '{');
            if ($jsonStart !== false) {
                $jsonStr = substr($jsonStr, $jsonStart);
            }

            $parsed = json_decode($jsonStr, true);
            if (!$parsed || !$parsed['status']) {
                $this->error("Failed to parse JSON output or parser returned status false for: {$filename}");
                continue;
            }

            $extracted = $parsed['data'];
            $confidence = $parsed['confidence'];

            // Validate critical fields
            if (empty($extracted['policy_number'])) {
                $this->error("Skipping: Policy number could not be extracted.");
                Log::channel('single')->warning("PDF Import: Skipping file {$filename} because policy number is empty.", $extracted);
                continue;
            }

            // Begin DB Transaction
            DB::beginTransaction();
            try {
                // 1. Resolve Company
                $companyId = null;
                if (!empty($extracted['company_name'])) {
                    $company = CompanyMaster::firstOrCreate(
                        ['name' => $extracted['company_name']],
                        ['status' => 1]
                    );
                    $companyId = $company->id;
                }

                // 2. Resolve Policy Type
                $policyTypeId = null;
                if (!empty($extracted['policy_type'])) {
                    $type = InsurancePolicyType::firstOrCreate(
                        ['name' => $extracted['policy_type']],
                        ['status' => 1]
                    );
                    $policyTypeId = $type->id;
                }

                // 3. Resolve Customer (Holder)
                $holderId = null;
                if (!empty($extracted['holder_name'])) {
                    // Try exact match first
                    $customer = Customer::where('name', $extracted['holder_name'])->first();
                    if (!$customer) {
                        // Create customer if doesn't exist
                        $customer = Customer::create([
                            'name' => $extracted['holder_name'],
                            'phone' => $extracted['holder_phone'] ?? null,
                            'email' => $extracted['holder_email'] ?? null,
                            'address' => $extracted['holder_address'] ?? null,
                            'type' => 'Individual',
                            'is_active' => 1,
                            'created_by' => 1
                        ]);
                    }
                    $holderId = $customer->id;
                }

                // 4. Save/Update Policy
                $policyData = [
                    'policy_number_no'   => $extracted['policy_number'],
                    'holder_id'          => $holderId,
                    'policy_type_id'     => $policyTypeId,
                    'company_id'         => $companyId,
                    'start_date'         => $extracted['start_date'] ?? null,
                    'end_date'           => $extracted['end_date'] ?? null,
                    'premium_amount'     => $extracted['premium'] ?? null,
                    'tax_amount'         => $extracted['tax_amount'] ?? null,
                    'sum_assured'        => $extracted['sum_assured'] ?? null,
                    'nominee_name'       => $extracted['nominee_name'] ?? null,
                    'nominee_contact'    => $extracted['nominee_contact'] ?? null,
                    
                    // Vehicle details
                    'vehicle_registration_number' => $extracted['vehicle_reg_no'] ?? null,
                    'vehicle_model'               => $extracted['vehicle_model'] ?? null,
                    'engine_number'               => $extracted['engine_no'] ?? null,
                    'chassis_number'              => $extracted['chassis_no'] ?? null,
                    'fuel_type'                   => $extracted['fuel_type'] ?? null,
                    'engine_capacity'             => $extracted['cc'] ?? null,
                    'gross_vehicle_weight'        => $extracted['gvw'] ?? null,
                    'no_claim_bonus'              => $extracted['ncb'] ?? null,
                    'vehicle_idv'                => $extracted['vehicle_idv'] ?? null,

                    'payment_amount'              => $extracted['amount'] ?? null,
                    'created_by'                  => 1,
                    'modified_by'                 => 1
                ];

                $policy = Policy::updateOrCreate(
                    ['policy_number' => $extracted['policy_number']],
                    $policyData
                );

                DB::commit();
                $successCount++;
                $this->info("Success: Imported Policy No: {$policy->policy_number} for {$extracted['holder_name']}");

                // Log confidence warnings for low confidence fields
                $lowConfidenceFields = [];
                foreach ($confidence as $field => $score) {
                    if ($score < 95) {
                        $lowConfidenceFields[$field] = $score;
                    }
                }

                if (!empty($lowConfidenceFields)) {
                    $this->warn("  Warnings (Low confidence fields):");
                    foreach ($lowConfidenceFields as $f => $s) {
                        $this->warn("    - {$f}: {$s}%");
                    }
                    Log::channel('single')->warning("PDF Import: Low confidence fields for {$filename} (Policy: {$extracted['policy_number']}):", $lowConfidenceFields);
                }

            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Error saving policy for {$filename}: " . $e->getMessage());
                Log::error("PDF Import Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            }
        }

        $this->info("==================================================");
        $this->info("Import completed: {$successCount} / {$totalCount} policies successfully imported.");
        return 0;
    }
}
