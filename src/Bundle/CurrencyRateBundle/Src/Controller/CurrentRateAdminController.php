<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Controller;

use App\Bundle\CurrencyRateBundle\Src\Entity\CurrentRate;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Sonata Admin controller for managing CurrentRate entities.
 *
 * Provides admin CRUD views (list, show, datagrid filters, form) for the
 * current_rate table, allowing administrators to inspect and view current exchange rates.
 *
 * @extends AbstractAdmin<CurrentRate>
 */
#[AutoconfigureTag('sonata.admin', [
    'model_class' => CurrentRate::class,
    'manager_type' => 'orm',
    'label' => 'Current rates'
])]
final class CurrentRateAdminController extends AbstractAdmin
{
    /**
     * Configures the datagrid filters for the CurrentRate list.
     *
     * Filters: baseCurrency, targetCurrency, value, date, updatedAt.
     *
     * @param DatagridMapper<CurrentRate> $filter The datagrid filter mapper
     */
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('baseCurrency', null, ['label' => 'Base Currency'])
            ->add('targetCurrency', null, ['label' => 'Target Currency'])
            ->add('value', null, ['label' => 'Rate'])
            ->add('date', null, ['label' => 'date'])
            ->add('updatedAt', null, ['label' => 'Updated']);
    }

    /**
     * Configures the columns displayed in the CurrentRate admin list view.
     *
     * Columns: baseCurrency, targetCurrency, value, updatedAt, and a show action.
     *
     * @param ListMapper<CurrentRate> $list The list field mapper
     */
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
                ],
            ]);
    }

    /**
     * Configures the form fields for creating/editing a CurrentRate entity.
     *
     * Fields: baseCurrency, targetCurrency, value.
     *
     * @param FormMapper<CurrentRate> $form The form field mapper
     */
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('baseCurrency', null, ['label' => 'Base Currency'])
            ->add('targetCurrency', null, ['label' => 'Target Currency'])
            ->add('value', null, ['label' => 'Rate']);
    }

    /**
     * Configures the fields displayed on the CurrentRate detail (show) page.
     *
     * Fields: baseCurrency, targetCurrency, value, updatedAt.
     *
     * @param ShowMapper<CurrentRate> $show The show field mapper
     */
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
}
