# NFSe GovBR
Pacote para geração de NFSe GovBR (Cachoeira do Sul) usando componentes NFePHP (https://github.com/nfephp-org)

Em desenvolvimento. Use por sua conta e risco.

## Instalação

**Este pacote é desenvolvido para uso do [Composer](https://getcomposer.org/), então não terá nenhuma explicação de instalação alternativa.**

```bash
composer require hadder/nfse-govbr
```
## DANFSe
Considerando que várias prefeituras tratam tanto o XML de retorno quanto o layout da DANFSe de forma autônomoa, a classe `Danfse` foi desenvolvida baseado no modelo de Cachoeira do Sul/RS.

### Cidades Atendidas
- Cachoeira do Sul/RS

### Serviços implementados
- GerarNfseEnvio
- ConsultarNfseRpsEnvio
- CancelarNfseEnvio

## Dependências
- ext-json
- ext-openssl
- ext-dom
- ext-curl
- ext-simplexml
- ext-gd

