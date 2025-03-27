<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use MRingler\Propel\Behavior\GenerateStrftimeGetters\GenerateStrftimeGettersBehavior;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\Column;
use Propel\Generator\Builder\Om\ObjectBuilder;

require_once __DIR__ . '/../vendor/autoload.php';

final class GenerateStrftimeGettersBehaviorTest extends TestCase
{
    const DATE_COLUMNS = [
        'DATE'      => 'MyDateColumn',
        'DATETIME'  => 'MyDatetimeColumn',
        'TIMESTAMP' => 'MyTimestampColumn'
    ];
    
    const NON_DATE_COLUMNS = [
        'INT'      => 'MyNonDateColumnColumn'
    ];
    
    public function testBehaviorGeneratesCode(): void
    {
        $script = $this->applyBehavior();
        $this->assertStringContainsString('public function', $script);
    }
    
    public function testBehaviorGeneratesCodeForAllColumns(): void
    {
        $script = $this->applyBehavior();
        
        foreach(self::DATE_COLUMNS as $columnName)
        {
            $expectedFunctionHeader = "public function get{$columnName}UsingLocale(";
            $this->assertStringContainsString($expectedFunctionHeader, $script);
        }
    }
    
    public function testBehaviorIgnoresNonDateColumns(): void
    {
        $script                 = $this->applyBehavior();
        $expectedFunctionHeader = "public function getMyNonDateColumnColumnUsingLocale(";
        $this->assertStringNotContainsString($expectedFunctionHeader, $script);
    }
    
    public function testColumnsParameterSpecifiesColumns(): void
    {
        $columns        = [self::DATE_COLUMNS['DATE'], self::DATE_COLUMNS['TIMESTAMP']];
        $unusedColumns  = [self::DATE_COLUMNS['DATETIME']];
        $paramValue     = implode(', ', $columns);
        $script         = $this->applyBehavior(['columns' => $paramValue]);
        
        foreach($columns as $columnName)
        {
            $expectedFunctionHeader = "public function get{$columnName}UsingLocale(";
            $this->assertStringContainsString($expectedFunctionHeader, $script);
        }
        
        foreach($unusedColumns as $columnName)
        {
            $expectedFunctionHeader = "public function get{$columnName}UsingLocale(";
            $this->assertStringNotContainsString($expectedFunctionHeader, $script);
        }
    }
    
    public function testFormatsParameterChangesMethodNames(): void
    {
        $format = 'myWhackyMethodNameFormat%sXD';
        $script = $this->applyBehavior(['function_name_format' => $format]);
        foreach(self::DATE_COLUMNS as $columnName)
        {
            $expectedName = sprintf($format, $columnName);
            $expectedFunctionHeader = "public function {$expectedName}(";
            $this->assertStringContainsString($expectedFunctionHeader, $script);
        }
    }
    
    public function testSpecifyingUnknownColumnThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->applyBehavior(['columns' => 'MyUnknownColumn']);
    }
    
    public function testSpecifyingNonDateColumnThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->applyBehavior(['columns' => 'MyNonDateColumn']);
    }
    
    public function testSpecifyingFormatWithoutReplacementThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->applyBehavior(['function_name_format' => 'noReplacement']);
    }
    
    public function testSpecifyingFormatWithMultipleReplacementThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->applyBehavior(['function_name_format' => 'multipleReplacements%s%s']);
    }
    
    private function applyBehavior(array $parameters = []): string
    {
        $behavior = $this->buildBehavior();
        foreach($parameters as $name => $value)
        {
            $behavior->addParameter(['name' => $name, 'value' => $value]);
        }
        $objectBuilder = new ObjectBuilder($behavior->getTable());
        return $behavior->objectMethods($objectBuilder);
    }
    
    private function buildBehavior(): GenerateStrftimeGettersBehavior
    {
        $behavior = new GenerateStrftimeGettersBehavior();
        $table    = $this->buildMockTable();
        $behavior->setTable($table);
        return $behavior;
    }
    
    private function buildMockTable(): Table
    {
        $table = new Table('TestTable');
        $columns = array_merge(self::DATE_COLUMNS, self::NON_DATE_COLUMNS);
        foreach($columns as $type => $name)
        {
            $column = new Column($name, $type);
            $column->setPhpName($name);
            $table->addColumn($column);
        }
        return $table;
    }
}