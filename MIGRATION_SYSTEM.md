# 🔄 Sistema de Migração de Dados - S-Invest v3.0.0

## ✨ Características do Sistema

### 🎯 Migração Completa
- **Investimentos**: Todos os dados e metadados
- **Taxonomias**: Modalidade → Tipo Produto com mapeamento inteligente
- **Aportes**: Relacionamentos SCP/Trade preservados
- **Usuários**: Roles investidor/associado
- **Integridade**: Verificação completa dos dados

### 🔧 Classe de Ativos como Select
- Campo único que determina SCP ou Trade
- Migração automática baseada em taxonomias existentes
- Interface administrativa simplificada

## 🚀 Como Usar

### 1. Acesso ao Sistema
1. Entre no **WordPress Admin** como administrador
2. Vá em **Ferramentas → Migração S-Invest**
3. O sistema mostrará o status atual e opções disponíveis

### 2. Verificações Pré-Migração
Antes de iniciar, o sistema verifica:
- ✅ Plugin sky-invest-panel ativo
- ✅ Dados existentes para migrar
- ✅ Sistema unificado configurado
- ✅ Permissões de banco de dados

### 3. Processo de Migração
```
[1] Backup Automático → [2] Migrar Taxonomias → [3] Migrar Investimentos → [4] Migrar Aportes → [5] Verificar Integridade
```

#### Etapa 1: Backup
- Backup automático das tabelas críticas
- Salvo em `/wp-content/backups/migration/`
- Restauração automática em caso de erro

#### Etapa 2: Taxonomias
- `modalidade` → `tipo_produto`
- Mapeamento inteligente:
  - SCP/Private → Categoria Private
  - Trade/Renda → Categoria Trade
  - Novos termos → Migração direta

#### Etapa 3: Investimentos
- Migração de todos os metadados ACF
- **Campo classe_de_ativos** configurado automaticamente
- Validação de dados obrigatórios
- Preservação de relacionamentos

#### Etapa 4: Aportes
- Migração com relacionamentos preservados
- Validação de valores numéricos
- Verificação de investidores válidos

#### Etapa 5: Verificação
- Contagem de registros migrados
- Validação de integridade referencial
- Relatório detalhado de erros (se houver)

## ⚙️ Configurações Avançadas

### Mapeamento de Taxonomias
```php
// Personalizar mapeamento no arquivo data-migration.php
$taxonomy_mapping = [
    'scp'     => 'private',
    'trade'   => 'trade',
    'renda'   => 'trade',
    'acoes'   => 'trade',
    'privado' => 'private'
];
```

### Campos Obrigatórios
```php
// Campos verificados na migração
$required_fields = [
    'valor_total',
    'total_captado', 
    'inicio_captacao',
    'fim_captacao',
    'classe_de_ativos' // ← Novo campo principal
];
```

## 🛡️ Segurança e Rollback

### Backup Automático
- Todas as tabelas são backup antes da migração
- Arquivos salvos com timestamp
- Compressão automática para economia de espaço

### Rollback em Caso de Erro
```sql
-- Em caso de problemas, executar:
-- 1. Restaurar backup das tabelas
-- 2. Reativar plugin sky-invest-panel  
-- 3. Verificar dados originais
```

### Log Detalhado
- Todas as operações são logadas
- Erros salvos em `/wp-content/debug.log`
- Relatório final com estatísticas

## 📊 Interface Administrativa

### Dashboard de Migração
- **Status Atual**: Plugin ativo/inativo, dados disponíveis
- **Progresso**: Barra de progresso em tempo real
- **Estatísticas**: Contadores de registros migrados
- **Logs**: Visualização de erros e sucessos

### Botões de Ação
- 🔄 **Iniciar Migração**: Processo completo automatizado
- 📊 **Verificar Dados**: Auditoria sem alterações
- 🗂️ **Backup Manual**: Criar backup antes de testes
- ⚡ **Migração Express**: Apenas dados essenciais

## 🔍 Resolução de Problemas

### Erro: "Plugin não encontrado"
```
Solução: Certifique-se de que sky-invest-panel está ativo
```

### Erro: "Falha na migração de taxonomias"
```
Solução: Verificar permissões de banco ou conflitos de slug
```

### Erro: "Campos ACF não encontrados"
```
Solução: Verificar se ACF Pro está ativo e campos configurados
```

### Erro: "Timeout na migração"
```
Solução: Aumentar memory_limit ou executar em lotes menores
```

## ✅ Pós-Migração

### Verificações Finais
1. **Teste o painel de investidores**: `/painel/`
2. **Verifique cálculos SCP/Trade**: Dashboard de associado
3. **Teste criação de novos aportes**
4. **Confirme funcionamento dos relatórios**

### Limpeza (Opcional)
Após confirmar que tudo funciona:
1. Desativar plugin `sky-invest-panel`
2. Remover backups antigos (se desejar)
3. Limpar logs de migração

### Campo Classe de Ativos
No cadastro de investimentos, agora você verá:
```
Classe de Ativos: [Private] [Trade]
```
- **Private**: Para SCP, Capital Semente, Participações
- **Trade**: Para Trading, Renda Variável, Ações

## 🎉 Benefícios do Sistema Migrado

### Para Administradores
- ✅ Sistema unificado no tema
- ✅ Controle total sem dependência de plugins
- ✅ Performance otimizada
- ✅ Facilidade de customização

### Para Usuários
- ✅ Interface mais rápida
- ✅ Experiência consistente
- ✅ Painel responsivo aprimorado
- ✅ Navegação intuitiva

---

**Migração desenvolvida pela equipe S-Invest**  
*Sistema Unificado v3.0.0 - Migração Inteligente*