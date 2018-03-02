<?php

namespace App\Command;

use App\Repository;
use function Jass\CardSet\byShortcuts;
use function Jass\CardSet\bySuitsAndValues;
use function Jass\CardSet\jassSet;
use function Jass\CardSet\suits;
use function Jass\CardSet\values;
use Jass\Entity\Card;
use Jass\Entity\Card\Suit;
use Jass\Entity\Card\Value;
use function Jass\Hand\ordered;
use Jass\MessageHandler;
use Jass\Style\BottomUp;
use Jass\Style\TopDown;
use Jass\Style\Trump;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ChooseStyle extends Command
{
    /**
     * @var Repository
     */
    private $repository;

    /**
     * @param Repository $repository
     */
    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('jass:choose')->setDescription('Chooses style according to cards');
        $this->addArgument('file', InputArgument::OPTIONAL, 'Add filename for ML output');
        $this->addArgument('cards', InputArgument::OPTIONAL, 'Cards. Use shortcuts');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cards = $input->getArgument('cards');
        if ($cards) {
            $cards = byShortcuts($cards);
        } else {
            $cards = jassSet();
            shuffle($cards);
            $cards = array_slice($cards, 0, 9);
        }

        $styles = [
            new TopDown(),
            new BottomUp(),
        ];
        foreach (suits() as $suit) {
            $styles[] = new Trump($suit);
        }

        $best = 0;
        $bestStyle = null;
        foreach ($styles as $style) {
            $points = array_sum(array_map(function(Card $card) use ($style) {
                return $style->points($card);
            }, $cards));
            if ($points > $best) {
                $bestStyle = $style;
                $best = $points;
            }
        }

        $file = $input->getArgument('file');
        if ($file) {
            $suits = [
                Suit::ROSE => '1000',
                Suit::SHIELD => '0100',
                Suit::OAK => '0010',
                Suit::BELL => '0001'
            ];
            $values = [
                Value::ACE =>   '100000000',
                Value::KING =>  '010000000',
                Value::QUEEN => '001000000',
                Value::JACK =>  '000100000',
                Value::TEN =>   '000010000',
                Value::NINE =>  '000001000',
                Value::EIGHT => '000000100',
                Value::SEVEN => '000000010',
                Value::SIX =>   '000000001',
            ];
            $styles = [
                'top down' =>     '100000',
                'bottom up' =>    '010000',
                'trump rose' =>   '001000',
                'trump shield' => '000100',
                'trump oak' =>    '000010',
                'trump bell' =>   '000001',
            ];

            $line = '';
            foreach ($cards as $card) {
                $line .= $suits[$card->suit];
                $line .= $values[$card->value];
            }
            $line .= ';';
            $line .= $styles[(string) $bestStyle];
            $line .= "\n";

            file_put_contents($file, $line, FILE_APPEND);

        } else {
            $output->writeln('Cards: ' . implode(', ', ordered($cards, $bestStyle->orderFunction())) . ', Style: ' . $bestStyle);
        }

    }

}