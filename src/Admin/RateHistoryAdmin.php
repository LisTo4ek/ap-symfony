<?php

declare(strict_types=1);

namespace App\Admin;

use App\Entity\RateHistory;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\DoctrineORMAdminBundle\Filter\DateFilter;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('sonata.admin', [
    'model_class' => RateHistory::class,
    'manager_type' => 'orm',
    'label' => 'Rate History'
])]
final class RateHistoryAdmin extends AbstractAdmin
{
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('date', DateFilter::class, [
                'label' => 'Дата',
                'field_type' => 'Symfony\Component\Form\Extension\Core\Type\DateType',
            ])
            ->add('charCode', null, ['label' => 'Currency'])
            ->add('value', null, ['label' => 'Rate']);
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('date', null, [
                'label' => 'Date',
                'format' => 'd.m.Y'
            ])
            ->add('charCode', null, ['label' => 'Currency'])
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
                'label' => 'Дата',
                'widget' => 'single_text'
            ])
            ->add('charCode', null, ['label' => 'Код валюты (3 символа)'])
            ->add('value', null, ['label' => 'Курс']);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('id')
            ->add('date', null, [
                'label' => 'Дата',
                'format' => 'd.m.Y'
            ])
            ->add('charCode', null, ['label' => 'Валюта'])
            ->add('value', null, ['label' => 'Курс']);
    }

    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues['_sort_order'] = 'DESC';
        $sortValues['_sort_by'] = 'date';
    }
}

