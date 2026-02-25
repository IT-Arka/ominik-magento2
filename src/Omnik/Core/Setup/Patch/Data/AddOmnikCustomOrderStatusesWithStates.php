<?php


declare(strict_types=1);

namespace Omnik\Core\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Sales\Model\Order\StatusFactory;
use Magento\Sales\Model\ResourceModel\Order\Status as StatusResource;

/**
 * Class AddOmnikCustomOrderStatusesWithStates
 *
 * Cria status customizados com seus próprios states correspondentes
 */
class AddOmnikCustomOrderStatusesWithStates implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * Configuração de status com states personalizados
     * Cada status terá seu próprio state correspondente
     */
    private const STATUS_CONFIG = [
        'approved' => [
            'label' => 'Approved',
            'state' => 'approved',
            'state_label' => 'Approved'
        ],
        'partiallyreturned' => [
            'label' => 'Partially Returned',
            'state' => 'partiallyreturned',
            'state_label' => 'Partially Returned'
        ],
        'partiallycanceled' => [
            'label' => 'Partially Canceled',
            'state' => 'partiallycanceled',
            'state_label' => 'Partially Canceled'
        ],
        'invoiced' => [
            'label' => 'Invoiced',
            'state' => 'invoiced',
            'state_label' => 'Invoiced'
        ],
        'sent' => [
            'label' => 'Sent',
            'state' => 'sent',
            'state_label' => 'Sent'
        ],
        'delivered' => [
            'label' => 'Delivered',
            'state' => 'delivered',
            'state_label' => 'Delivered'
        ],
        'shipping_label' => [
            'label' => 'Shipping Label',
            'state' => 'shipping_label',
            'state_label' => 'Shipping Label'
        ],
        'error_order' => [
            'label' => 'Error Order',
            'state' => 'error_order',
            'state_label' => 'Error Order'
        ],
        'receiving_period' => [
            'label' => 'Receiving Period',
            'state' => 'receiving_period',
            'state_label' => 'Receiving Period'
        ],
        'reverse_request' => [
            'label' => 'Reverse Request',
            'state' => 'reverse_request',
            'state_label' => 'Reverse Request'
        ],
        'reverse_in_progress' => [
            'label' => 'Reverse In Progress',
            'state' => 'reverse_in_progress',
            'state_label' => 'Reverse In Progress'
        ],
        'reverse_receive' => [
            'label' => 'Reverse Receive',
            'state' => 'reverse_receive',
            'state_label' => 'Reverse Receive'
        ],
        'reverse_canceled' => [
            'label' => 'Reverse Canceled',
            'state' => 'reverse_canceled',
            'state_label' => 'Reverse Canceled'
        ],
        'reverse_concluded' => [
            'label' => 'Reverse Concluded',
            'state' => 'reverse_concluded',
            'state_label' => 'Reverse Concluded'
        ]
    ];

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var StatusFactory
     */
    private $statusFactory;

    /**
     * @var StatusResource
     */
    private $statusResource;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param StatusFactory $statusFactory
     * @param StatusResource $statusResource
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        StatusFactory $statusFactory,
        StatusResource $statusResource
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->statusFactory = $statusFactory;
        $this->statusResource = $statusResource;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        try {
            // Criar todos os status customizados
            $this->createCustomStatuses();
            
            // Criar states customizados e associar aos status
            $this->createStatesAndAssignStatuses();
            
        } catch (\Exception $e) {
            throw new \Exception('Erro ao criar status e states customizados Omnik: ' . $e->getMessage());
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Cria todos os status de pedido customizados
     * 
     * @return void
     * @throws \Exception
     */
    private function createCustomStatuses(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $tableName = $this->moduleDataSetup->getTable('sales_order_status');

        foreach (self::STATUS_CONFIG as $statusCode => $config) {
            // Verificar se o status já existe
            $select = $connection->select()
                ->from($tableName, 'status')
                ->where('status = ?', $statusCode);
            
            $existingStatus = $connection->fetchOne($select);

            if (!$existingStatus) {
                // Inserir o novo status
                $connection->insert(
                    $tableName,
                    [
                        'status' => $statusCode,
                        'label' => $config['label']
                    ]
                );
            }
        }
    }

    /**
     * Cria states customizados e associa os status correspondentes
     * 
     * @return void
     * @throws \Exception
     */
    private function createStatesAndAssignStatuses(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $statusStateTable = $this->moduleDataSetup->getTable('sales_order_status_state');
        $statusLabelTable = $this->moduleDataSetup->getTable('sales_order_status_label');

        foreach (self::STATUS_CONFIG as $statusCode => $config) {
            $stateCode = $config['state'];
            $stateLabel = $config['state_label'];
            
            // Verificar se a associação status-state já existe
            $select = $connection->select()
                ->from($statusStateTable, ['status', 'state'])
                ->where('status = ?', $statusCode)
                ->where('state = ?', $stateCode);
            
            $existingAssociation = $connection->fetchRow($select);

            if (!$existingAssociation) {
                // Criar a associação status-state (isso efetivamente cria o state)
                $connection->insert(
                    $statusStateTable,
                    [
                        'status' => $statusCode,
                        'state' => $stateCode,
                        'is_default' => 1, // Cada status é padrão do seu próprio state
                        'visible_on_front' => 1
                    ]
                );
            }

            // Adicionar label do status para store 0 (admin) se não existir
            $selectLabel = $connection->select()
                ->from($statusLabelTable, ['status', 'store_id'])
                ->where('status = ?', $statusCode)
                ->where('store_id = ?', 0);
            
            $existingLabel = $connection->fetchRow($selectLabel);

            if (!$existingLabel) {
                $connection->insert(
                    $statusLabelTable,
                    [
                        'status' => $statusCode,
                        'store_id' => 0,
                        'label' => $config['label']
                    ]
                );
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function revert()
    {
        $connection = $this->moduleDataSetup->getConnection();

        foreach (array_keys(self::STATUS_CONFIG) as $statusCode) {
            // Remover labels dos status
            $connection->delete(
                $this->moduleDataSetup->getTable('sales_order_status_label'),
                ['status = ?' => $statusCode]
            );

            // Remover associações status-state (isso remove efetivamente os states customizados)
            $connection->delete(
                $this->moduleDataSetup->getTable('sales_order_status_state'),
                ['status = ?' => $statusCode]
            );

            // Remover os status customizados
            $connection->delete(
                $this->moduleDataSetup->getTable('sales_order_status'),
                ['status = ?' => $statusCode]
            );
        }
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
