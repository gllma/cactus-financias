Este projeto usa dedoc/scramble para gerar documentação OpenAPI automaticamente a partir do código. A documentação deve ser boa o suficiente para qualquer dev consumir a API sem precisar tirar dúvidas. Para isso, SEMPRE seguir estas práticas:

## Controllers

Cada controller DEVE ter o atributo `#[Group]` para organização:

```php
use Dedoc\Scramble\Attributes\Group;

#[Group('Viagens')]
class TripBookingController extends Controller
```

Cada método DEVE ter PHPDoc com:
- Descrição clara do que o endpoint faz (para quem, quando usar)
- `@param` para path parameters com descrição
- `@throws` para exceções que o endpoint pode lançar

```php
/**
 * Confirma uma nova solicitação de viagem.
 *
 * O passageiro PNE solicita transporte informando destino, data, horário e motivo.
 * Origem é sempre o endereço cadastrado do passageiro.
 * Se ida e volta, gera dois tickets vinculados (RN08).
 *
 * @param ConfirmTripBookingRequest $request
 * @throws \App\Packages\TripBooking\Exceptions\UserAlreadyHasTripException
 */
public function confirm(ConfirmTripBookingRequest $request): JsonResponse
```

## FormRequests

As rules() do FormRequest são lidas automaticamente pelo Scramble. Adicionar comentários descritivos acima de cada campo para gerar descrições no OpenAPI:

```php
public function rules(): array
{
    return [
        // ID do endereço de destino (cadastrado pelo passageiro)
        'destination_id' => ['required', 'integer', 'exists:social.addresses,id'],
        // Data desejada para a viagem
        'date' => ['required', 'date', 'after:today'],
        // Horário preferencial de embarque
        'time' => ['required', 'date_format:H:i'],
        // Motivo da viagem (Saúde, Trabalho, Lazer, Outro)
        'reason_id' => ['required', 'integer', 'exists:trip.trip_reasons,id'],
        // Se precisa de transporte de ida e volta
        'round_trip' => ['required', 'boolean'],
        // Se será acompanhado por outra pessoa
        'has_companion' => ['required', 'boolean'],
        // Horário do compromisso no destino (usado para priorização)
        'appointment_time' => ['required', 'date_format:H:i'],
        // Observações adicionais para o gestor
        'notes' => ['nullable', 'string', 'max:500'],
    ];
}
```

## Resources (JsonResource)

SEMPRE anotar o model da resource e descrever campos não óbvios:

```php
/**
 * @mixin \App\Packages\TripBooking\Models\TripBooking
 */
class TripBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            /** Status atual da solicitação (pending, in_route, completed, cancelled) */
            'status' => $this->status,
            /** @var string Data da viagem no formato ISO 8601 */
            'date' => $this->date->toISOString(),
            /** Dados do passageiro */
            'passenger' => new PersonResource($this->whenLoaded('person')),
            /** @var array{lat: float, lng: float} Coordenadas do destino */
            'destination_coordinates' => $this->destination_coordinates,
        ];
    }
}
```

## Enums

Descrever cada caso do enum para que apareça na documentação:

```php
enum TripBookingStatusEnum: string
{
    /** Aguardando análise do gestor */
    case PENDING = 'pending';
    /** Alocada em uma rota pelo gestor */
    case IN_ROUTE = 'in_route';
    /** Horário reajustado pelo gestor */
    case ADJUSTED = 'adjusted';
}
```

## Path Parameters

Sempre anotar path params com descrição:

```php
/**
 * @param TripBooking $tripBooking ID da solicitação de viagem
 */
public function show(TripBooking $tripBooking): JsonResponse
```

Ou via atributo para controle total:

```php
use Dedoc\Scramble\Attributes\PathParameter;

#[PathParameter('id', description: 'ID da rota', type: 'integer', example: 42)]
public function show(int $id): JsonResponse
```

## Query Parameters

Para endpoints de listagem com filtros, documentar:

```php
use Dedoc\Scramble\Attributes\QueryParameter;

#[QueryParameter('status', description: 'Filtrar por status', type: 'string', example: 'pending', enum: ['pending', 'in_route', 'completed', 'cancelled'])]
#[QueryParameter('page', description: 'Página da listagem', type: 'integer', default: 1)]
#[QueryParameter('per_page', description: 'Itens por página', type: 'integer', default: 15)]
public function index(IndexTripBookingRequest $request): JsonResponse
```

## Regras

- SEMPRE adicionar `#[Group('Nome do Grupo')]` no controller
- SEMPRE adicionar PHPDoc descritivo em CADA método público do controller
- SEMPRE comentar campos do FormRequest rules() (Scramble lê como descrição)
- SEMPRE anotar `@mixin Model` ou `@property Model $resource` nas Resources
- SEMPRE usar `@throws` para exceções documentáveis
- SEMPRE descrever campos de enum com PHPDoc
- Campos com formato especial: usar `@format date-time`, `@format email`, `@format uuid`
- Campos com exemplo: usar `@example "valor"`
- A documentação deve ser suficiente para um dev consumir a API sem precisar tirar dúvidas
