<?php

namespace Victoire\Bundle\BlogBundle\Filter;

use Victoire\Bundle\FilterBundle\Filter\BaseFilter;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

/**
 * DateFilter form type
 */
class DateFilter extends BaseFilter
{
    protected $em;
    protected $request;

    /**
     * Constructor
     *
     * @param EntityManager $em
     * @param unknown       $request
     */
    public function __construct(EntityManager $em, $request)
    {
        $this->em = $em;
        $this->request = $request;
    }

    /**
     * Build the query
     *
     * @param QueryBuilder &$qb
     * @param array        $parameters
     *
     * @return queryBuilder
     */
    public function buildQuery(QueryBuilder $qb, array $parameters)
    {
        $emConfig = $this->em->getConfiguration();
        $emConfig->addCustomDatetimeFunction('YEAR', 'DoctrineExtensions\Query\Mysql\Year');
        $emConfig->addCustomDatetimeFunction('MONTH', 'DoctrineExtensions\Query\Mysql\Month');
        $emConfig->addCustomDatetimeFunction('DAY', 'DoctrineExtensions\Query\Mysql\Day');

        if (isset($parameters['year'])) {
            $qb->andWhere('YEAR(main_item.publishedAt) = :year')
                ->setParameter('year', $parameters['year']);
        }
        if (isset($parameters['month'])) {
            $qb->andWhere('MONTH(main_item.publishedAt) = :month')
                ->setParameter('month', $parameters['month']);
        }
        if (isset($parameters['day'])) {
            $qb->andWhere('DAY(main_item.publishedAt) = :day')
                ->setParameter('day', $parameters['day']);
        }

        return $qb;
    }

    /**
     * define form fields
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @SuppressWarnings checkUnusedFunctionParameters
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $articles = $this->em->getRepository('VictoireBlogBundle:Article')->getAll()->run();
        $years = $months = $days = array();
        foreach ($articles as $key => $_article) {
            $years[$_article->getPublishedAt()->format('Y')] = $_article->getPublishedAt()->format('Y');

            if ($options['widget']->getFormat() != 'year') {
                //init $months array
                if (!isset($months[$_article->getPublishedAt()->format('Y')])) {
                    $months[$_article->getPublishedAt()->format('Y')] = array();
                }
                $months[$_article->getPublishedAt()->format('Y')][] = $_article->getPublishedAt()->format('M');
                if ($options['widget']->getFormat() != 'month') {
                    //init $days array
                    if (!isset($days[$_article->getPublishedAt()->format('M')])) {
                        $days[$_article->getPublishedAt()->format('M')] = array();
                    }
                    //assign values
                    $days[$_article->getPublishedAt()->format('M')][] = $_article->getPublishedAt()->format('M');
                }
            }
        }

        $data = array('year' => null, 'month' => null, 'day' => null);
        if ($this->request->query->has('filter') && array_key_exists('date_filter', $this->request->query->get('filter'))) {
            $_request = $this->request->query->get('filter')['date_filter'];
            $data = $_request;
        }

        if (in_array($options['widget']->getFormat(), array('year', 'month', 'day'))) {
            $builder
                ->add(
                    'year', 'choice', array(
                        'label'       => false,
                        'choices'     => $years,
                        'required'    => false,
                        'expanded'    => true,
                        'multiple'    => false,
                        'empty_value' => false,
                        'data'        => $data['year'],
                    )
                );
            //@todo implements me for month and day
            // if (in_array($options['widget']->getFormat(), array('month', 'day'))) {
            //     if (in_array($options['widget']->getFormat(), array('day'))) {
            //     }
            // }
        }
    }

    /**
     * Get the filters
     *
     * @param array $filters
     *
     * @return array The filters
     */
    public function getFilters($filters)
    {
        return $this->em->getRepository('VictoireBlogBundle:Article')->findAll();
    }

    /**
     * get form name
     * @return string name
     */
    public function getName()
    {
        return 'date_filter';
    }
}