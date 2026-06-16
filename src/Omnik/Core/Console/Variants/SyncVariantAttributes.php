<?php

declare(strict_types=1);

namespace Omnik\Core\Console\Variants;

use Omnik\Core\Model\Integration\Variant\GetList;
use Omnik\Core\Model\Service\VariantAttributeManager;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Sincroniza TODAS as variantes da Omnik como atributos de produto no Magento.
 *
 * Para cada variante retornada pela API de variantes da Omnik:
 *  - garante a existência do atributo (variant_<nome>) em todos os attribute sets;
 *  - importa todas as opções (values), sem duplicar.
 *
 * Deixa o catálogo de atributos 100% pronto para o cliente fazer o de-para
 * (Stores > Configuration > Omnik > Mapeamento de Atributos de Produto).
 */
class SyncVariantAttributes extends Command
{
    /**
     * Limite de páginas por segurança contra loop infinito.
     */
    private const MAX_PAGES = 1000;

    /**
     * Tamanho da página ao paginar a API de variantes.
     * 10 é o default aceito pela API da Omnik (mesmo usado pelo ImportVariants).
     */
    private const PAGE_SIZE = 10;

    /**
     * @param State $state
     * @param GetList $getList
     * @param VariantAttributeManager $attributeManager
     */
    public function __construct(
        private readonly State                   $state,
        private readonly GetList                 $getList,
        private readonly VariantAttributeManager $attributeManager
    ) {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('omnik:variants:sync-attributes')
            ->setDescription(
                'Importa TODAS as variantes da Omnik como atributos de produto (cria atributo + opções)'
            );

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->state->setAreaCode('global');
        } catch (\Exception $e) {
            // área já definida — segue
        }

        $offset = 0;
        $page = 0;
        $totalVariants = 0;
        $totalOptions = 0;
        $total = null;

        do {
            $response = $this->getList->execute($offset, self::PAGE_SIZE);

            if (!is_array($response) || !isset($response['values'])) {
                $output->writeln('<error>Resposta inválida da API de variantes da Omnik.</error>');
                $output->writeln('<comment>Resposta recebida (offset=' . $offset . '):</comment>');
                $output->writeln(is_scalar($response)
                    ? (string)$response
                    : var_export($response, true));
                return Command::FAILURE;
            }

            if ($total === null) {
                $total = (int)($response['total'] ?? 0);
                $output->writeln(sprintf('Total de variantes na Omnik: %d', $total));
            }

            $values = $response['values'];
            if (empty($values)) {
                break;
            }

            foreach ($values as $variant) {
                $variantName = trim((string)($variant['variantData']['name'] ?? ''));
                if ($variantName === '') {
                    continue;
                }

                $attributeCode = $this->attributeManager->ensureAttribute($variantName);
                $created = $this->attributeManager->importOptions(
                    $attributeCode,
                    $variant['values'] ?? []
                );

                $totalVariants++;
                $totalOptions += $created;

                $output->writeln(sprintf(
                    '  ✔ %-15s → %s (%d nova(s) opção(ões))',
                    $variantName,
                    $attributeCode,
                    $created
                ));
            }

            $offset += self::PAGE_SIZE;
            $page++;
        } while ($offset < $total && $page < self::MAX_PAGES);

        $output->writeln('');
        $output->writeln(sprintf(
            '<info>Concluído: %d variante(s) processada(s), %d opção(ões) nova(s) criada(s).</info>',
            $totalVariants,
            $totalOptions
        ));
        $output->writeln(
            'Agora os atributos estão disponíveis no de-para: '
            . 'Stores > Configuration > Omnik > Mapeamento de Atributos de Produto.'
        );

        return Command::SUCCESS;
    }
}
