<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Type;

class AnalyzeSchemaCommand extends Command
{
    protected $signature = 'schema:analyze {--save= : Save report to storage (txt, json, md)}';
    protected $description = 'Analyze database schema and suggest possible enhancements for performance and structure.';

    public function handle()
    {
        $this->info('🔍 Analyzing database schema...');

        $connection = DB::connection();

        // ✅ Fix for ENUM support in MariaDB / MySQL
        $platform = $connection->getDoctrineConnection()->getDatabasePlatform();
        if (! $platform->hasDoctrineTypeMappingFor('enum')) {
            $platform->registerDoctrineTypeMapping('enum', 'string');
        }
        if (! $platform->hasDoctrineTypeMappingFor('json')) {
            $platform->registerDoctrineTypeMapping('json', 'text');
        }

        try {
            $schemaManager = $connection->getDoctrineSchemaManager();
        } catch (Exception $e) {
            $this->error('⚠️  Failed to load schema manager: ' . $e->getMessage());
            return;
        }

        $tables = $schemaManager->listTables();
        $suggestions = [];

        foreach ($tables as $table) {
            $tableName = $table->getName();
            $indexes = $table->getIndexes();
            $columns = $table->getColumns();
            $columnNames = array_map(fn($c) => $c->getName(), $columns);

            $this->line("📦 Scanning table: <comment>{$tableName}</comment>");

            // === Basic checks ===
            if (! $table->hasPrimaryKey()) {
                $suggestions[] = "⚠️ Table '{$tableName}' has no primary key.";
            }

            if (! in_array('created_at', $columnNames) || ! in_array('updated_at', $columnNames)) {
                $suggestions[] = "🕒 Table '{$tableName}' missing timestamps (created_at / updated_at).";
            }

            if (! in_array('deleted_at', $columnNames)) {
                $suggestions[] = "🗑️ Table '{$tableName}' missing soft delete column (deleted_at).";
            }

            // === Analyze columns ===
            foreach ($columns as $column) {
                $name = $column->getName();
                $type = $column->getType()->getName();
                $length = $column->getLength();
                $isNullable = $column->getNotnull() ? 'NO' : 'YES';

                // Foreign keys and indexes
                if (Str::endsWith($name, '_id')) {
                    if (! $this->hasIndex($indexes, $name)) {
                        $suggestions[] = "🧩 Table '{$tableName}' - add index on '{$name}' (likely foreign key).";
                    }
                    if (! $this->hasForeignKey($schemaManager, $tableName, $name)) {
                        $suggestions[] = "🔗 Table '{$tableName}' - '{$name}' looks like foreign key but has no FK constraint.";
                    }
                }

                // Morph columns (nullable morphs)
                if (Str::endsWith($name, '_type')) {
                    $base = Str::beforeLast($name, '_type');
                    $idColumn = $base . '_id';
                    if (in_array($idColumn, $columnNames)) {
                        if (! $this->hasCompositeIndex($indexes, [$idColumn, $name])) {
                            $suggestions[] = "🎭 Table '{$tableName}' - consider composite index on ('{$idColumn}', '{$name}') for morphable relation.";
                        }
                        if ($isNullable === 'YES') {
                            $suggestions[] = "ℹ️ Table '{$tableName}' - '{$idColumn}' and '{$name}' are nullable morphs, ensure consistent null handling.";
                        }
                    }
                }

                // Common lookup fields
                if (preg_match('/(email|slug|uuid|code)$/', $name) && ! $this->hasIndex($indexes, $name)) {
                    $suggestions[] = "🚀 Table '{$tableName}' - consider indexing '{$name}' for fast lookups.";
                }

                // Status / type / flag columns
                if (preg_match('/(status|type|is_active|enabled|category_id)$/', $name) && ! $this->hasIndex($indexes, $name)) {
                    $suggestions[] = "⚙️ Table '{$tableName}' - consider indexing '{$name}' for query filtering.";
                }

                // Nullable long text
                if ($isNullable === 'YES' && in_array($type, ['text', 'mediumtext', 'longtext'])) {
                    $suggestions[] = "📄 Table '{$tableName}' - nullable {$type} column '{$name}' may slow down queries.";
                }

                // Oversized varchar
                if ($type === 'string' && $length > 255) {
                    $suggestions[] = "📏 Table '{$tableName}' - '{$name}' VARCHAR({$length}) too large, use TEXT or reduce length.";
                }
            }

            // === Composite index suggestions ===
            $commonCombos = [
                ['user_id', 'created_at'],
                ['category_id', 'status'],
                ['type', 'created_at'],
            ];

            foreach ($commonCombos as $combo) {
                if (count(array_intersect($combo, $columnNames)) === count($combo)) {
                    if (! $this->hasCompositeIndex($indexes, $combo)) {
                        $suggestions[] = "⚡ Table '{$tableName}' - consider composite index on (" . implode(', ', $combo) . ").";
                    }
                }
            }

            // === Redundant indexes ===
            $indexedColumns = [];
            foreach ($indexes as $index) {
                foreach ($index->getColumns() as $col) {
                    $indexedColumns[$col][] = $index->getName();
                }
            }

            foreach ($indexedColumns as $col => $idxs) {
                if (count($idxs) > 1) {
                    $suggestions[] = "❗ Table '{$tableName}' - column '{$col}' has multiple indexes (" . implode(', ', $idxs) . ").";
                }
            }

            // === Too many indexes ===
            if (count($indexes) > 6) {
                $suggestions[] = "📉 Table '{$tableName}' - has " . count($indexes) . " indexes; too many may slow writes.";
            }

            // seperator between tables
            $suggestions[] = "---------------------------------";
        }

        // === Output summary ===
        $this->newLine();
        $this->info('🧾 Schema Analysis Report');
        $this->line(str_repeat('─', 60));

        if (empty($suggestions)) {
            $this->info('✅ No major schema enhancements detected. Schema looks solid!');
        } else {
            foreach ($suggestions as $s) {
                $this->warn("• {$s}");
            }
            $this->newLine();
        }

        if ($this->option('save')) {
            $this->saveReport($suggestions, $this->option('save'));
        }

        $this->newLine();
        $this->info('🎯 Schema analysis completed.');
    }

    private function hasIndex($indexes, $column)
    {
        foreach ($indexes as $index) {
            if (in_array($column, $index->getColumns())) {
                return true;
            }
        }
        return false;
    }

    private function hasCompositeIndex($indexes, array $columns)
    {
        foreach ($indexes as $index) {
            if ($columns === $index->getColumns()) {
                return true;
            }
        }
        return false;
    }

    private function hasForeignKey($schemaManager, $table, $column)
    {
        foreach ($schemaManager->listTableForeignKeys($table) as $fk) {
            if (in_array($column, $fk->getLocalColumns())) {
                return true;
            }
        }
        return false;
    }

    private function saveReport($suggestions, $format)
    {
        $path = storage_path("schema_report.{$format}");
        $data = null;

        switch ($format) {
            case 'json':
                $data = json_encode($suggestions, JSON_PRETTY_PRINT);
                break;
            case 'md':
                $data = "# Schema Optimization Report\n\n";
                foreach ($suggestions as $s) $data .= "- {$s}\n";
                break;
            default:
                $data = implode(PHP_EOL, $suggestions);
        }

        file_put_contents($path, $data);
        $this->info("💾 Report saved to: {$path}");
    }
}
