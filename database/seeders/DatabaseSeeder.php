<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Arena;
use App\Models\Group;
use App\Models\User;
use App\Models\Schedule;
use App\Models\Fight;
use App\Models\Bet_log;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        DB::table('arena')->truncate();
        $arena = Arena::factory()->create();

        DB::table('group')->truncate();
        $group = Group::factory()->create([
            'arena_id' => $arena->id
        ]);

        DB::table('user')->truncate();
        $users = User::factory()->count(4)
            ->state(new Sequence(
                ['user_type_id' => 1],
                ['user_type_id' => 2],
                ['user_type_id' => 3],
                ['user_type_id' => 4],
            ))
            ->create([
            'group_id' => $group->id
        ]);

        DB::table('schedule')->truncate();

        // ENV variables
        $seeder_start_date = env('SEEDER_START_DATE') ? Carbon::createFromFormat('Y-m-d', env('SEEDER_START_DATE')) : Carbon::now();
        $seeder_end_date = env('SEEDER_END_DATE') ? Carbon::createFromFormat('Y-m-d', env('SEEDER_END_DATE')) : Carbon::now();
        $seeder_min_fights = env('SEEDER_MIN_FIGHTS') ?? 10;
        $seeder_max_fights = env('SEEDER_MAX_FIGHTS') ?? 20;
        $seeder_min_bet_count = env('SEEDER_MIN_BET_COUNT') ?? 100;
        $seeder_max_bet_count = env('SEEDER_MAX_BET_COUNT') ?? 200;
        $seeder_min_bet_amount = env('SEEDER_MIN_BET_AMOUNT') ?? 100;
        $seeder_max_bet_amount = env('SEEDER_MAX_BET_AMOUNT') ?? 1000;

        DB::table('fight')->truncate();
        DB::table('bet_log')->truncate();

        while ($seeder_start_date->lessThanOrEqualTo($seeder_end_date)) {

            $total_fights = rand($seeder_min_fights, $seeder_max_fights);

            $schedule = Schedule::factory()->create([
                'arena_id' => $arena->id,
                'user_id' => $users[1]->id,
                'total_fights' => $total_fights,
                'datetime' => $seeder_start_date->format('Y-m-d 00:00:00'),
                'open_at' => $seeder_start_date->format('Y-m-d 00:00:00'),
                'close_at' => $seeder_start_date->format('Y-m-d 23:59:59'),
            ]);

            for ($x=1; $x <= $total_fights; $x++) { 
                // Generate Fight incrementing by 1
                $fight = Fight::factory()->create([
                    'arena_id' => $arena->id,
                    'schedule_id' => $schedule->id,
                    'admin_id' => $users[2]->id,
                    'fight_no' => $x,
                    'announcement' => Carbon::now()->format('H:i:s.u'),
                ]);

                // Generate bet submit limit by random bet_count
                $bet_count = rand($seeder_min_bet_count, $seeder_max_bet_count);
                $bet_log_bulk = [];
                for ($y=1; $y <= $bet_count; $y++) {
                    // IF variable y is MOD by 10 THEN pick draw 
                    // ELSE IF variable y is MOD by 2 THEN pick meron
                    // ELSE pick wala
                    $bet_select = (($y % 10) == 0) ? 3 : ($y % 2 ? 1 : 2);
                    $bet_amount = ($bet_select == 3) ? rand($seeder_min_bet_amount, $schedule->max_draw_bet) : rand($seeder_min_bet_amount, $seeder_max_bet_amount);

                    array_push($bet_log_bulk, [
                        'user_id' => $users[2]->id,
                        'schedule_id' => $schedule->id,
                        'fight_id' => $fight->id,
                        'bet_select' => $bet_select,
                        'bet_amount' => $bet_amount,
                        'barcode' => Carbon::now()->format('Hisu')
                    ]);
                }

                DB::table('bet_log')->insert($bet_log_bulk);

                // Close fight
                DB::table('fight')->where('id', $fight->id)->update(['status' => 0]);

                // Get MERON total bet
                $meron_query = DB::table('bet_log')
                                ->select(DB::raw('IFNULL(SUM(bet_amount), 0) AS `total`, COUNT(id) AS `count`'))
                                ->where('schedule_id', $schedule->id)
                                ->where('fight_id', $fight->id)
                                ->where('bet_select', 1)
                                ->first();
                $meron_bet = $meron_query->total;
                $meron_count = $meron_query->count;

                // Get WALA total bet
                $wala_query = DB::table('bet_log')
                                ->select(DB::raw('IFNULL(SUM(bet_amount), 0) AS `total`, COUNT(id) AS `count`'))
                                ->where('schedule_id', $schedule->id)
                                ->where('fight_id', $fight->id)
                                ->where('bet_select', 2)
                                ->first();
                $wala_bet = $wala_query->total;
                $wala_count = $wala_query->count;

                // Get DRAW total bet
                $draw_query = DB::table('bet_log')
                                ->select(DB::raw('IFNULL(SUM(bet_amount), 0) AS `total`, COUNT(id) AS `count`'))
                                ->where('schedule_id', $schedule->id)
                                ->where('fight_id', $fight->id)
                                ->where('bet_select', 3)
                                ->first();
                $draw_bet = $draw_query->total;
                $draw_count = $draw_query->count;

                $total_bet = $meron_bet + $wala_bet;
                $rake = $schedule->rake_percentage / 100;
                $owner_profit = $total_bet * $rake;
                $base_total = $total_bet - $owner_profit;
                $meron_payout = number_format(($base_total / $meron_bet) * 100, 2);
                $wala_payout = number_format(($base_total / $wala_bet) * 100, 2);

                DB::table('fight')
                    ->where('id', $fight->id)
                    ->update([
                        'meron_bet' => $meron_bet,
                        'wala_bet' => $wala_bet,
                        'draw_bet' => $draw_bet,
                        'meron_count' => $meron_count,
                        'wala_count' => $wala_count,
                        'draw_count' => $draw_count,
                        'meron_payout' => $meron_payout,
                        'wala_payout' => $wala_payout,
                        'total_amount' => $total_bet,
                        // 'total_commission' => $owner_profit,
                    ]);
                
                // Update bet_log.result_amount
                DB::table('bet_log')
                    ->where('schedule_id', $schedule->id)
                    ->where('fight_id', $fight->id)
                    ->update([
                        'result_amount' => DB::raw('(CASE bet_select 
                        WHEN 1 THEN bet_amount * ' . number_format($meron_payout / 100, 2) . '
                        WHEN 2 THEN bet_amount * ' . number_format($wala_payout / 100, 2) . '
                        WHEN 3 THEN bet_amount * ' . $schedule->draw_rake . '
                        END)')
                    ]);
                
                // Generate Fight Winner
                $magic_int = rand(1, 100);
                $winner = (($magic_int % 10) == 0) ? 3 : ($magic_int % 2 ? 1 : 2);

                // Update bet_log win
                DB::table('bet_log')
                    ->where('schedule_id', $schedule->id)
                    ->where('fight_id', $fight->id)
                    ->update([
                        'result' => $winner
                    ]);

                DB::table('bet_log')
                    ->where('schedule_id', $schedule->id)
                    ->where('fight_id', $fight->id)
                    ->where('bet_select', $winner)
                    ->update([
                        'status' => 1
                    ]);

                if ($winner == 3) {
                    // Update bet_log draw / return bet
                    DB::table('bet_log')
                    ->where('schedule_id', $schedule->id)
                    ->where('fight_id', $fight->id)
                    ->where('bet_select', '<>', $winner)
                    ->update([
                        'status' => 3,
                        'result_amount' => DB::raw('bet_amount')
                    ]);
                } else {
                    // Update bet_log lose
                    DB::table('bet_log')
                    ->where('schedule_id', $schedule->id)
                    ->where('fight_id', $fight->id)
                    ->where('bet_select', '<>', $winner)
                    ->update([
                        'status' => 2
                    ]);
                }

                $total_win = DB::table('bet_log')
                    ->select(DB::raw('IFNULL(SUM(result_amount), 0) AS `total`'))
                    ->where('schedule_id', $schedule->id)
                    ->where('fight_id', $fight->id)
                    ->where('status', 1)
                    ->first()->total;

                // Refresh fight variable
                $fight = Fight::find($fight->id);
                
                $draw_commission = 0;
                $total_commission = $fight->total_amount - $total_win;
                if ($winner == 3) {
                    $draw_commission = $total_win;
                    $total_commission = 0;
                }

                DB::table('fight')
                    ->where('id', $fight->id)
                    ->update([
                        'result' => $winner,
                        'draw_commission' => $draw_commission,
                        'total_commission' => $total_commission,
                    ]);
                $this->command->info($seeder_start_date->format('Y-m-d') . ' / ' . $seeder_end_date->format('Y-m-d') . ' ==> Fight No. ' . $x . ' / ' . $total_fights);
            }

            $seeder_start_date->addDay();
        }
        
    }
}
