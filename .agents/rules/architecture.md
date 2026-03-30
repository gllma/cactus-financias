Arquitetura modular por Packages em `app/Packages/{PackageName}/`.

Camadas obrigatórias (NUNCA pular):
Controller → Service → Repository → Model

- Controller: recebe request, chama service, retorna response. ZERO lógica de negócio.
- Service: lógica de negócio. Método `execute()`. Recebe DTO. Usa `DB::transaction()` para escrita.
- Repository: extends `BaseRepository` com `setModel()`. Únicolocal para queries.
- Model: `$fillable`, `$casts`, relationships. Sem lógica.

Naming:
- Controller: `{Entity}Controller`
- Service: `{Action}{Entity}Service`
- Repository: `{Entity}Repository`
- DTO: `{Action}{Entity}DTO extends Spatie\LaravelData\Data`
- FormRequest: `{Action}{Entity}Request`
- Resource: `{Entity}Resource`
- Enum: `{Entity}{Concept}Enum`

Responses via trait `App\Base\Traits\Response`:
- `self::successResponse($data, __('modulo.chave'), 200)`
- `self::returnError(__('modulo.chave'), 400)`
- SEMPRE retornar Resource, NUNCA Model direto

## FormRequests — Padrão Obrigatório

O `AuthenticateMiddleware` faz `catch (Exception $exception)` que captura `ValidationException` antes do handler do Laravel. O `returnError()` trata `ValidationException` convertendo para 422, mas FormRequests DEVEM usar o padrão de `failedValidation` para consistência com o código existente:

```php
use App\Base\Traits\Response;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class ExemploRequest extends FormRequest
{
    use Response;

    public function authorize(): bool { return true; }

    public function rules(): array { return [...]; }

    protected function failedValidation(Validator $validator)
    {
        return self::failedValidationResponse($validator);
    }
}
```

- SEMPRE adicionar `use Response;` e `failedValidation()` em novos FormRequests
- Sem esse override, a validação FUNCIONA (retorna 422) mas não segue o padrão do projeto

## Queries SQL

- TODA query SQL (`DB::select`, `DB::selectOne`, `DB::table()`, `DB::raw()`) DEVE estar em Repository
- Services NUNCA fazem queries — chamam o Repository
- Se uma query é usada por múltiplos Services, criar método no Repository e reutilizar
- Violação comum: Services com `DB::selectOne()` para buscar status atual — mover para `Repository::getCurrentStatus()`

## DTOs

- Dados recebidos via DTO DEVEM ser efetivamente persistidos
- Se o DTO tem `reason`, `fields`, `notes` → verificar que são salvos no banco
- Violação comum: DTO recebe `reason` mas o Service só salva `status_id` e ignora `reason`
