<?php

declare(strict_types=1);

namespace App\Admin;

use App\Domain\Action\CurrencyRate\ProcessCbrCurrencyRateHistoryAction;
use App\Domain\Contracts\CurrentRateStorageContract;
use App\Entity\CurrentRate;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('sonata.admin', [
    'model_class' => CurrentRate::class,
    'manager_type' => 'orm',
    'label' => 'Текущие курсы'
])]
final class CurrentRateAdmin extends AbstractAdmin
{
    public function __construct(
        private readonly CurrentRateStorageContract $currentRateStorageContract,
        private readonly ProcessCbrCurrencyRateHistoryAction $processCbrCurrencyRateHistoryAction,
    ) {
        parent::__construct();
    }
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('baseCurrency', null, ['label' => 'Base Currency'])
            ->add('targetCurrency', null, ['label' => 'Target Currency'])
            ->add('value', null, ['label' => 'Rate'])
            ->add('date', null, ['label' => 'date'])
            ->add('updatedAt', null, ['label' => 'Updated']);
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('baseCurrency', null, ['label' => 'Base Currency'])
            ->add('targetCurrency', null, ['label' => 'Target Currency'])
            ->add('value', null, ['label' => 'Rate'])
            ->add('updatedAt', null, [
                'label' => 'Updated',
                'format' => 'd.m.Y H:i:s'
            ])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'label' => 'Actions',
                'actions' => [
                    'show' => [],
//                    'edit' => [],
                ],
            ]);
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('baseCurrency', null, ['label' => 'Base Currency'])
            ->add('targetCurrency', null, ['label' => 'Target Currency'])
            ->add('value', null, ['label' => 'Rate']);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('baseCurrency', null, ['label' => 'Base Currency'])
            ->add('targetCurrency', null, ['label' => 'Target Currency'])
            ->add('value', null, ['label' => 'Rate'])
            ->add('updatedAt', null, [
                'label' => 'Updated',
                'format' => 'd.m.Y H:i:s'
            ]);
    }

    protected function configureQuery(ProxyQueryInterface $query, ): ProxyQueryInterface
    {
        $today = new \DateTimeImmutable('today');

        if (!$this->currentRateStorageContract->hasRecordsByDay($today)) {
            ($this->processCbrCurrencyRateHistoryAction)($today);
        }

        return $query;
    }
}

