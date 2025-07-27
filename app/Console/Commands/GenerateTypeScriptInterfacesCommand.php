<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionMethod;

final class GenerateTypeScriptInterfacesCommand extends Command
{
    protected $signature = 'neon:generate-types';
    protected $description = 'Generate TypeScript interfaces from Laravel models';

    public function handle(): int
    {
        $modelsPath = app_path('Models');
        $outputPath = resource_path('js/types/models.d.ts');

        if (! File::exists($modelsPath)) {
            $this->error('Models directory not found!');

            return 1;
        }

        $interfaces = [];
        $modelFiles = File::allFiles($modelsPath);

        foreach ($modelFiles as $file) {
            $className = $this->getClassNameFromFile($file);

            if ($className && class_exists($className)) {
                $reflection = new ReflectionClass($className);

                if ($reflection->isSubclassOf('\Illuminate\Database\Eloquent\Model') && ! $reflection->isAbstract()) {
                    $this->info("Processing: {$className}");
                    $interfaces[] = $this->generateInterface($reflection);
                }
            }
        }

        $output = "// Auto-generated TypeScript interfaces from Laravel models\n";
        $output .= '// Generated at: ' . now()->toIso8601String() . "\n\n";
        $output .= implode("\n\n", $interfaces);

        File::ensureDirectoryExists(dirname($outputPath));
        File::put($outputPath, $output);

        $this->info("TypeScript interfaces generated at: {$outputPath}");

        return 0;
    }

    private function getClassNameFromFile($file): ?string
    {
        $relativePath = str_replace(app_path() . '/', '', $file->getPathname());
        $className = str_replace(['/', '.php'], ['\\', ''], $relativePath);

        return 'App\\' . $className;
    }

    private function generateInterface(ReflectionClass $reflection): string
    {
        $modelName = $reflection->getShortName();
        $interfaceName = $modelName;
        $properties = [];
        $propertyMap = [];

        // Get model instance
        $instance = $reflection->newInstanceWithoutConstructor();
        $fillable = $instance->getFillable();
        $casts = $instance->getCasts();
        $dates = $instance->getDates();
        $appends = $instance->getAppends();

        // Extract properties from PHPDoc
        $docComment = $reflection->getDocComment();
        if ($docComment) {
            preg_match_all('/@property\s+([^\s]+)\s+\$([a-zA-Z_][a-zA-Z0-9_]*)/', $docComment, $matches);
            for ($i = 0; $i < count($matches[0]); $i++) {
                $propertyName = $matches[2][$i];
                $propertyType = $matches[1][$i];
                $tsType = $this->phpToTypeScript($propertyType);
                $propertyMap[$propertyName] = "    {$propertyName}?: {$tsType};";
            }
        }

        // Add fillable properties (override PHPDoc if exists)
        foreach ($fillable as $field) {
            $type = $this->getTypeFromCast($field, $casts);
            $propertyMap[$field] = "    {$field}?: {$type};";
        }

        // Handle special primary key
        $primaryKey = $instance->getKeyName();
        $keyType = $instance->getKeyType();
        if ($primaryKey !== 'id') {
            $propertyMap[$primaryKey] = "    {$primaryKey}?: " . ($keyType === 'string' ? 'string' : 'number') . ';';
        } elseif (! isset($propertyMap['id'])) {
            $propertyMap['id'] = '    id?: ' . ($keyType === 'string' ? 'string' : 'number') . ';';
        }

        // Ensure timestamps exist
        if (! isset($propertyMap['created_at'])) {
            $propertyMap['created_at'] = '    created_at?: string;';
        }
        if (! isset($propertyMap['updated_at'])) {
            $propertyMap['updated_at'] = '    updated_at?: string;';
        }

        // Add date properties
        foreach ($dates as $date) {
            if (! isset($propertyMap[$date])) {
                $propertyMap[$date] = "    {$date}?: string;";
            }
        }

        // Add appended attributes
        foreach ($appends as $append) {
            if (! isset($propertyMap[$append])) {
                $propertyMap[$append] = "    {$append}?: any;";
            }
        }

        // Add relationships
        $relationships = $this->getRelationships($reflection);
        foreach ($relationships as $name => $type) {
            $propertyMap[$name] = "    {$name}?: {$type};";
        }

        $interface = "export interface {$interfaceName} {\n";
        $interface .= implode("\n", $propertyMap);
        $interface .= "\n}";

        return $interface;
    }

    private function getTypeFromCast(string $field, array $casts): string
    {
        if (! isset($casts[$field])) {
            return 'string';
        }

        $cast = $casts[$field];

        return match ($cast) {
            'int', 'integer' => 'number',
            'real', 'float', 'double' => 'number',
            'string' => 'string',
            'bool', 'boolean' => 'boolean',
            'object', 'array', 'json', 'collection' => 'Record<string, any>',
            'date', 'datetime', 'timestamp' => 'string',
            'decimal' => 'string',
            default => str_contains($cast, 'decimal:') ? 'string' : 'any',
        };
    }

    private function getRelationships(ReflectionClass $reflection): array
    {
        $relationships = [];
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if ($method->class !== $reflection->getName() ||
                $method->getNumberOfParameters() > 0 ||
                $method->isStatic()) {
                continue;
            }

            $returnType = $method->getReturnType();
            if (! $returnType) {
                continue;
            }

            $typeName = $returnType->getName();

            if (str_contains($typeName, 'Illuminate\\Database\\Eloquent\\Relations')) {
                $relationName = $method->getName();

                if (str_contains($typeName, 'HasMany') || str_contains($typeName, 'BelongsToMany') || str_contains($typeName, 'MorphMany')) {
                    $relationships[$relationName] = $this->getRelatedModelName($method) . '[]';
                } else {
                    $relationships[$relationName] = $this->getRelatedModelName($method);
                }
            }
        }

        return $relationships;
    }

    private function getRelatedModelName(ReflectionMethod $method): string
    {
        try {
            // Try to get the relation by calling the method
            $class = $method->getDeclaringClass();
            $instance = $class->newInstanceWithoutConstructor();

            // Read the method source to find the related model
            $filename = $method->getFileName();
            $start_line = $method->getStartLine() - 1;
            $end_line = $method->getEndLine();
            $length = $end_line - $start_line;

            $source = file($filename);
            $body = implode('', array_slice($source, $start_line, $length));

            // Look for common relationship patterns
            if (preg_match('/->(?:hasMany|hasOne|belongsTo|belongsToMany|morphMany|morphOne|morphTo|morphToMany)\s*\(\s*([A-Za-z_\\\\]+)::class/', $body, $matches)) {
                $modelClass = $matches[1];
                // Extract just the class name
                if (str_contains($modelClass, '\\')) {
                    $parts = explode('\\', $modelClass);

                    return end($parts);
                }

                return $modelClass;
            }

            // Try via doc comment
            $docComment = $method->getDocComment();
            if ($docComment && preg_match('/@return.*\\\\([A-Za-z]+)/', $docComment, $matches)) {
                return $matches[1];
            }
        } catch (Exception $e) {
            // Fallback
        }

        return 'any';
    }

    private function phpToTypeScript(string $phpType): string
    {
        // Remove nullable indicator
        $phpType = str_replace('|null', '', $phpType);
        $phpType = str_replace('?', '', $phpType);

        // Handle union types - take first non-null type
        if (str_contains($phpType, '|')) {
            $types = explode('|', $phpType);
            $phpType = $types[0];
        }

        return match ($phpType) {
            'int', 'integer' => 'number',
            'float', 'double', 'real' => 'number',
            'string' => 'string',
            'bool', 'boolean' => 'boolean',
            'array' => 'any[]',
            'object' => 'Record<string, any>',
            'mixed' => 'any',
            '\\Carbon\\Carbon', '\\Illuminate\\Support\\Carbon' => 'string',
            default => str_contains($phpType, '\\') ? 'any' : $phpType,
        };
    }
}
