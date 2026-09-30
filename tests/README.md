# 🧪 Testes Unitários - WooCommerce Cielo Payment Gateway

## 📊 Status: 100% Completo ✅

**Total de Testes:** 32 principais (119 subtestes)  
**Cobertura:** ~78% do código crítico  
**Framework:** PHPUnit 9.5 + Brain\Monkey 2.6 + Mockery 1.5

---

## 🚀 Início Rápido

### 1. Instalar Dependências

```bash
composer install
```

### 2. Executar Todos os Testes

```bash
composer test
# ou
vendor/bin/phpunit
```

### 3. Executar Suite Específica

```bash
# PIX (8 testes, 32 subtestes)
composer test -- --testsuite=pix

# Credit Card (10 testes, 42 subtestes)
composer test -- --testsuite=credit

# Refund (5 testes, 13 subtestes)
composer test -- --testsuite=refund

# Logging (5 testes, 15 subtestes)
composer test -- --testsuite=logging

# Hooks (4 testes, 17 subtestes)
composer test -- --testsuite=hooks
```

### 4. Gerar Relatório de Cobertura

```bash
composer test:coverage
# Abre coverage/index.html
```

---

## 📁 Estrutura de Testes

```
tests/
├── bootstrap.php              # Inicialização Brain\Monkey
├── TestCase.php              # Classe base para testes
├── TestHelpers.php           # Fixtures e helpers
│
├── Unit/
    ├── Pix/                  # 5 arquivos, 32 subtestes
    │   ├── PixQrCodeGenerationTest.php
    │   ├── PixCronJobTest.php
    │   ├── PixStatusPollingTest.php
    │   ├── PixDataMaskingTest.php
    │   └── PixErrorHandlingTest.php
    │
    ├── Credit/               # 3 arquivos, 42 subtestes
    │   ├── CreditCardValidationTest.php
    │   ├── CreditAuthorizationTest.php
    │   └── CreditErrorHandlingTest.php
    │
    ├── Refund/               # 1 arquivo, 13 subtestes
    │   └── RefundProcessTest.php
    │
    ├── Logging/              # 1 arquivo, 15 subtestes
    │   └── LoggingTest.php
    │
    └── Hooks/                # 1 arquivo, 17 subtestes
        └── HooksExecutionTest.php
│
└── js/                       # Testes de JavaScript (Node) — ver tests/js/README.md
```

---

## 🧩 Testes de JavaScript (Node)

Além da suíte PHP (PHPUnit), os scripts de front-end têm testes em `tests/js/`
(Node puro, sem dependências). Veja **`tests/js/README.md`**.

```bash
node tests/js/lkn-card-fields.autocomplete.test.js
```

> A pasta `tests/` **não** é empacotada no `.zip` de release — os workflows do
> GitHub Actions usam whitelist (`mv` de `includes/`, `resources/`, etc.).

---

## 🎯 O Que é Testado

### Módulo PIX (8 testes)
- ✅ Geração de QR Code
- ✅ Agendamento de cron jobs
- ✅ Polling de status (Status 2, 3, 10, 12)
- ✅ Auto-limpeza após 2 horas
- ✅ Erro de credenciais inválidas
- ✅ Erro de API offline
- ✅ Mascaramento CPF/CNPJ
- ✅ Salvamento de metadata

### Módulo Credit Card (10 testes)
- ✅ Validação de número de cartão
- ✅ Validação de data de validade
- ✅ Validação de CVV
- ✅ Autorização com captura imediata
- ✅ Autorização com captura diferida
- ✅ Cálculo de parcelas
- ✅ Negação de pagamento
- ✅ Erro de rede
- ✅ Salvamento de metadata
- ✅ Mascaramento de número de cartão

### Módulo Refund (5 testes)
- ✅ Estorno total
- ✅ Estorno parcial
- ✅ Estorno de transação cancelada
- ✅ Erro de rede no estorno
- ✅ Filtro customizado de estorno

### Módulo Logging (5 testes)
- ✅ Debug mode ON salva logs
- ✅ Debug mode OFF não salva logs
- ✅ Credenciais mascaradas
- ✅ Order meta logs salvos
- ✅ Metabox exibido no admin

### Módulo Hooks (4 testes)
- ✅ lkn_wc_cielo_change_order_status
- ✅ Filtro de estorno customizado
- ✅ Zero Auth hook
- ✅ Suporte a features

---

## 🔧 Tecnologias Utilizadas

### PHPUnit 9.5
Framework de testes unitários para PHP

### Brain\Monkey 2.6
Biblioteca para mockar funções WordPress sem instalar WordPress real

**Vantagens:**
- ✅ Testes 100% em memória
- ✅ Execução rápida (~1-2 segundos)
- ✅ Zero dependências externas
- ✅ Funciona sem WordPress instalado

### Mockery 1.5
Biblioteca para criar mocks de classes (WC_Order, WC_Logger, etc)

**Uso:**
```php
$mockOrder = Mockery::mock('WC_Order');
$mockOrder->shouldReceive('get_total')->andReturn(100.00);
```

---

## 📚 Exemplos de Uso

### Exemplo 1: Testar Método Privado (Reflection)

```php
public function test_validate_card_number()
{
    $gateway = new LknWCGatewayCieloCredit();
    
    // Usar Reflection para acessar método privado
    $reflection = new \ReflectionClass($gateway);
    $method = $reflection->getMethod('validate_card_number');
    $method->setAccessible(true);
    
    // Executar método
    $result = $method->invoke($gateway, '4111111111111111', false);
    
    // Assert
    $this->assertTrue($result);
}
```

### Exemplo 2: Mockar Funções WordPress

```php
public function test_with_wordpress_functions()
{
    // Mockar get_option
    Functions\when('get_option')->alias(function($key) {
        return ['env' => 'sandbox', 'debug' => 'yes'];
    });
    
    // Mockar wp_remote_post
    Functions\when('wp_remote_post')->justReturn([
        'body' => json_encode(['Status' => 2]),
        'response' => ['code' => 201]
    ]);
    
    // Seu código de teste aqui...
}
```

### Exemplo 3: Mockar Classes WooCommerce

```php
public function test_with_wc_order()
{
    // Criar mock de WC_Order
    $mockOrder = Mockery::mock('WC_Order');
    $mockOrder->shouldReceive('get_total')->andReturn(100.00);
    $mockOrder->shouldReceive('update_status')->once();
    
    // Mockar wc_get_order
    Functions\when('wc_get_order')->justReturn($mockOrder);
    
    // Seu código de teste aqui...
}
```

---

## 🎓 Padrões e Convenções

### Nomenclatura de Testes

```php
/**
 * @test
 * Teste [Número].[Letra]: Descrição clara do que está sendo testado
 */
public function test_descriptive_name_in_snake_case()
{
    // Arrange - Preparar dados
    $input = 'test';
    
    // Act - Executar ação
    $result = doSomething($input);
    
    // Assert - Verificar resultado
    $this->assertEquals('expected', $result);
}
```

### Organização de Arquivos

- 1 arquivo de teste por funcionalidade principal
- Múltiplos métodos de teste no mesmo arquivo
- Subtestes relacionados agrupados

### Assertions Customizadas

```php
// TestCase.php
protected function assertStringIsMasked(string $value)
{
    $this->assertStringContainsString('*', $value);
    $this->assertNotEmpty($value);
}

// Uso
$this->assertStringIsMasked($maskedCard);
```

---

## 🔐 Segurança nos Testes

### Dados NUNCA Expostos
- ❌ CVV/CVC completo
- ❌ Número de cartão completo
- ❌ CPF/CNPJ completo
- ❌ MerchantKey completo

### Sempre Mascarado
- ✅ Cartões: `411111******1111`
- ✅ CPF: `123.***.***-00`
- ✅ Credenciais: `ABC**********XYZ`

### Exemplo de Teste de Mascaramento

```php
public function test_card_number_is_masked()
{
    $original = '4111111111111111';
    $masked = maskCardNumber($original);
    
    // Assert
    $this->assertStringNotContainsString($original, $masked);
    $this->assertStringContainsString('*', $masked);
    $this->assertEquals('411111******1111', $masked);
}
```

---

## 📊 Estatísticas

| Métrica | Valor |
|---------|-------|
| Testes principais | 32 |
| Subtestes | 119 |
| Arquivos de teste | 11 |
| Linhas de código | ~4.340 |
| Cobertura estimada | ~78% |
| Tempo de execução | ~1-2s |

---

## 🐛 Troubleshooting

### Erro: "vendor/bin/phpunit not found"

```bash
# Instalar dependências
composer install
```

### Erro: "Class 'WC_Order' not found"

Isso é esperado! Os testes usam Brain\Monkey para mockar WordPress.
Não é necessário instalar WordPress real.

### Testes falhando após mudanças

```bash
# Limpar cache do composer
composer dump-autoload

# Re-executar testes
composer test
```

### Ver testes específicos com falha

```bash
# Modo verbose
vendor/bin/phpunit --verbose

# Parar no primeiro erro
vendor/bin/phpunit --stop-on-failure
```

---

## 📖 Documentação Adicional

- **RELATORIO_TESTES.md** - Relatório completo de implementação
- **MAPEAMENTO_TECNICO_ACOPLAMENTO.md** - Mapeamento técnico do plugin
- **phpunit.xml** - Configuração do PHPUnit
- **tests/bootstrap.php** - Inicialização do ambiente de testes

---

## 🤝 Contribuindo

### Adicionando Novos Testes

1. Criar arquivo em `tests/Unit/[Modulo]/`
2. Estender `Lkn\WCCieloPaymentGateway\Tests\TestCase`
3. Usar Brain\Monkey para funções WP
4. Usar Mockery para classes WC
5. Adicionar suite no `phpunit.xml` se necessário

### Exemplo de Novo Teste

```php
<?php
namespace Lkn\WCCieloPaymentGateway\Tests\Unit\MyModule;

use Lkn\WCCieloPaymentGateway\Tests\TestCase;
use Brain\Monkey\Functions;

class MyModuleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Setup específico
    }
    
    /**
     * @test
     */
    public function test_my_feature()
    {
        // Arrange
        Functions\when('get_option')->justReturn(['key' => 'value']);
        
        // Act
        $result = myFunction();
        
        // Assert
        $this->assertTrue($result);
    }
}
```

---

## 📞 Suporte

Para dúvidas ou problemas:
1. Verificar **RELATORIO_TESTES.md**
2. Consultar exemplos nos arquivos de teste existentes
3. Ler documentação do [Brain\Monkey](https://brain-wp.github.io/BrainMonkey/)
4. Ler documentação do [PHPUnit](https://phpunit.de/documentation.html)

---

**Última atualização:** 2026-03-14  
**Status:** ✅ 100% Completo - Pronto para Produção
