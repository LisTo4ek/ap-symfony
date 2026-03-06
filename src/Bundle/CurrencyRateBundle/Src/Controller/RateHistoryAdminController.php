<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Controller;

use App\Bundle\CurrencyRateBundle\Src\Entity\RateHistory;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\DoctrineORMAdminBundle\Filter\DateFilter;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @extends AbstractAdmin<RateHistory>
 */
#[AutoconfigureTag('sonata.admin', [
    'model_class' => RateHistory::class,
    'manager_type' => 'orm',
    'label' => 'Rate History'
])]
final class RateHistoryAdminController extends AbstractAdmin
{
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('date', DateFilter::class, [
                'label' => 'Date',
                'field_type' => 'Symfony\Component\Form\Extension\Core\Type\DateType',
            ])
            ->add('baseCurrency', null, ['label' => 'Base Currency'])
            ->add('targetCurrency', null, ['label' => 'Target Currency'])
            ->add('value', null, ['label' => 'Rate']);
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('date', null, [
                'label' => 'Date',
                'format' => 'd.m.Y'
            ])
            ->add('baseCurrency', null, ['label' => 'Base Currency'])
            ->add('targetCurrency', null, ['label' => 'Target Currency'])
            ->add('value', null, ['label' => 'Rate'])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'label' => 'Actions',
                'actions' => [
                    'show' => [],
                    'edit' => [],
                    'delete' => [],
                ],
            ]);
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('date', null, [
                'label' => 'Date',
                'widget' => 'single_text'
            ])
            ->add('baseCurrency', null, ['label' => 'Base Currency'])
            ->add('targetCurrency', null, ['label' => 'Target Currency'])
            ->add('value', null, ['label' => 'Rate']);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('date', null, [
                'label' => 'Date',
                'format' => 'd.m.Y'
            ])
            ->add('baseCurrency', null, ['label' => 'Base Currency'])
            ->add('targetCurrency', null, ['label' => 'Target Currency '])
            ->add('value', null, ['label' => 'Rate']);
    }

    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues['_sort_order'] = 'DESC';
        $sortValues['_sort_by'] = 'date';
    }
}
