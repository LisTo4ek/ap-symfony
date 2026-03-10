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
 * Sonata Admin controller for managing RateHistory entities.
 *
 * Provides admin CRUD views (list, show, edit, delete, datagrid filters, form) for the
 * rate_history table, allowing administrators to inspect and manage historical exchange rates.
 * Default sort is by date descending.
 *
 * @extends AbstractAdmin<RateHistory>
 */
#[AutoconfigureTag('sonata.admin', [
    'model_class' => RateHistory::class,
    'manager_type' => 'orm',
    'label' => 'Rate History'
])]
final class RateHistoryAdminController extends AbstractAdmin
{
    /**
     * Configures the datagrid filters for the RateHistory list.
     *
     * Filters: date (DateFilter), baseCurrency, targetCurrency, value.
     *
     * @param DatagridMapper<RateHistory> $filter The datagrid filter mapper
     */
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

    /**
     * Configures the columns displayed in the RateHistory admin list view.
     *
     * Columns: date, baseCurrency, targetCurrency, value, and show/edit/delete actions.
     *
     * @param ListMapper<RateHistory> $list The list field mapper
     */
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

    /**
     * Configures the form fields for creating/editing a RateHistory entity.
     *
     * Fields: date (single_text widget), baseCurrency, targetCurrency, value.
     *
     * @param FormMapper<RateHistory> $form The form field mapper
     */
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

    /**
     * Configures the fields displayed on the RateHistory detail (show) page.
     *
     * Fields: id, date, baseCurrency, targetCurrency, value.
     *
     * @param ShowMapper<RateHistory> $show The show field mapper
     */
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

    /**
     * Sets the default sort order for the RateHistory list to date descending.
     *
     * @param array<string, string> $sortValues Sort configuration array (modified by reference)
     */
    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues['_sort_order'] = 'DESC';
        $sortValues['_sort_by'] = 'date';
    }
}
