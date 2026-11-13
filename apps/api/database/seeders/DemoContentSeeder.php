<?php

namespace Database\Seeders;

use App\Models\AccessOffer;
use App\Models\PackageItem;
use App\Models\PackageSeries;
use App\Models\PaymentMethod;
use App\Models\PointBundle;
use App\Models\Question;
use App\Models\QuestionSeries;
use App\Models\StudentAccount;
use App\Models\TestPackage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $owner = $this->seedStaff('owner', 'Owner', 'Fibo Owner');
        $reviewer = $this->seedStaff('reviewer', 'Reviewer', 'Fibo Reviewer');

        $rows = [
            ['Q-1284', 3, 'Perbandingan panjang sisi pada segitiga', 'Pilihan ganda', 'Geometri dan Pengukuran', 'Penalaran', 'Pada segitiga siku-siku ABC, panjang sisi siku-sikunya $6$ cm dan $8$ cm. Berapa panjang sisi miringnya?', ['8 cm', '9 cm', '10 cm', '14 cm'], ['answerKey' => 2], 'Gunakan teorema Pythagoras: $c=\\sqrt{6^2+8^2}=\\sqrt{100}=10$ cm.', 'Pemeriksaan', $owner, $reviewer],
            ['Q-1283', 2, 'Peluang mengambil bola bernomor genap', 'PG kompleks · MCMA', 'Data dan Peluang', 'Aplikasi', 'Sebuah kotak berisi bola bernomor $1$ sampai $8$. Pilih semua pernyataan yang benar.', ['Peluang mengambil bilangan genap adalah $\\frac{1}{2}$.', 'Peluang mengambil bilangan prima adalah $\\frac{1}{4}$.', 'Peluang mengambil bilangan lebih dari 6 adalah $\\frac{1}{4}$.', 'Peluang mengambil angka 1 adalah $\\frac{1}{8}$.'], ['correctOptions' => [0, 2, 3]], 'Ada empat bilangan genap dari delapan bola. Bilangan prima adalah 2, 3, 5, dan 7, jadi peluangnya $\\frac{4}{8}=\\frac{1}{2}$.', 'Draf', $owner, null],
            ['Q-1282', 1, 'Pola bilangan pada susunan kursi', 'Pilihan ganda', 'Bilangan', 'Pengetahuan', 'Baris pertama memiliki $5$ kursi. Setiap baris berikutnya memiliki $3$ kursi lebih banyak. Berapa banyak kursi pada baris ke-6?', ['17', '20', '23', '25'], ['answerKey' => 1], 'Pola bertambah 3. Baris ke-6 adalah $5+5\\times3=20$.', 'Disetujui', $reviewer, $owner],
            ['Q-1281', 4, 'Model biaya perjalanan', 'PG kompleks · Kategori', 'Aljabar', 'Aplikasi', 'Biaya perjalanan taksi dinyatakan dengan $B=7.000+4.000x$, dengan $x$ adalah jarak dalam kilometer. Tentukan benar atau salah.', [], ['categoryStatements' => ['Biaya awal perjalanan adalah Rp7.000.', 'Untuk jarak 5 km, biaya perjalanan Rp27.000.'], 'categoryAnswers' => ['Benar', 'Benar']], 'Substitusi $x=5$: $B=7.000+4.000(5)=27.000$.', 'Terbit', $reviewer, $owner],
            ['Q-1280', 2, 'Luas permukaan prisma segitiga', 'Pilihan ganda', 'Geometri dan Pengukuran', 'Penalaran', 'Sebuah prisma memiliki luas alas $12$ cm$^2$, keliling alas $18$ cm, dan tinggi prisma $7$ cm. Berapa luas permukaannya?', ['126 cm²', '138 cm²', '150 cm²', '162 cm²'], ['answerKey' => 2], 'Luas permukaan prisma adalah $2L_a+K_a\\times t=2(12)+18(7)=150$ cm².', 'Terbit', $owner, $reviewer],
            ['Q-1279', 1, 'Membaca diagram garis penjualan', 'PG kompleks · MCMA', 'Data dan Peluang', 'Pengetahuan', 'Diagram menunjukkan jumlah penjualan selama 5 hari. Pilih semua kesimpulan yang sesuai dengan data.', ['Penjualan tertinggi terjadi pada hari ke-4.', 'Penjualan selalu meningkat.', 'Rata-rata penjualan adalah 24 unit.', 'Penjualan terendah terjadi pada hari pertama.'], ['correctOptions' => [0, 3]], 'Baca nilai maksimum, minimum, dan pola pada diagram sebelum menyimpulkan.', 'Arsip', $reviewer, $owner],
        ];

        foreach ($rows as [$code, $revision, $title, $format, $topic, $level, $prompt, $options, $answer, $explanation, $status, $author, $reviewerFor]) {
            $series = QuestionSeries::firstOrCreate(['code' => $code], ['created_by' => $author->id]);
            Question::firstOrCreate(
                ['question_series_id' => $series->id, 'revision' => $revision],
                [
                    'title' => $title,
                    'format' => $format,
                    'education_level' => 'SMP/MTs',
                    'subject' => 'Matematika',
                    'framework_version' => '2025.1',
                    'topic' => $topic,
                    'subelement' => $topic,
                    'cognitive_level' => $level,
                    'prompt' => $prompt,
                    'options' => $options,
                    'answer_data' => $answer,
                    'explanation' => $explanation,
                    'status' => $status,
                    'author_id' => $author->id,
                    'reviewer_id' => $reviewerFor?->id,
                    'approved_by' => in_array($status, ['Disetujui', 'Terbit', 'Arsip'], true) ? $reviewerFor?->id : null,
                    'submitted_at' => $status === 'Draf' ? null : now(),
                    'approved_at' => in_array($status, ['Disetujui', 'Terbit', 'Arsip'], true) ? now() : null,
                    'published_at' => in_array($status, ['Terbit', 'Arsip'], true) ? now() : null,
                    'archived_at' => $status === 'Arsip' ? now() : null,
                ],
            );
        }

        foreach ([
            ['PK-024', 1, 'Simulasi TKA Matematika #4', 90, 428, 'Terbit', [['Q-1281', 4], ['Q-1280', 2]]],
            ['PK-023', 2, 'Latihan Aljabar: Persamaan & Fungsi', 45, 1206, 'Terbit', [['Q-1281', 4]]],
            ['PK-022', 1, 'Paket Pemantapan Geometri', 60, 0, 'Draf', [['Q-1280', 2], ['Q-1281', 4]]],
        ] as [$code, $version, $name, $duration, $attempts, $status, $pins]) {
            $series = PackageSeries::firstOrCreate(['code' => $code], ['created_by' => $owner->id]);
            $package = TestPackage::firstOrCreate(
                ['package_series_id' => $series->id, 'version' => $version],
                [
                    'name' => $name,
                    'education_level' => 'SMP/MTs',
                    'subject' => 'Matematika',
                    'framework_version' => '2025.1',
                    'duration_minutes' => $duration,
                    'attempts' => $attempts,
                    'status' => $status,
                    'author_id' => $owner->id,
                    'reviewer_id' => $status === 'Draf' ? null : $reviewer->id,
                    'approved_by' => $status === 'Terbit' ? $reviewer->id : null,
                    'submitted_at' => $status === 'Terbit' ? now() : null,
                    'approved_at' => $status === 'Terbit' ? now() : null,
                    'published_at' => $status === 'Terbit' ? now() : null,
                ],
            );

            foreach ($pins as $position => [$questionCode, $questionRevision]) {
                $question = Question::query()
                    ->where('revision', $questionRevision)
                    ->whereHas('series', fn ($query) => $query->where('code', $questionCode))
                    ->firstOrFail();
                PackageItem::firstOrCreate([
                    'test_package_id' => $package->id,
                    'question_id' => $question->id,
                ], ['position' => $position + 1]);
            }
        }

        foreach ([
            ['Transfer Bank BCA', 'Transfer bank', 'BCA'],
            ['Transfer Bank Mandiri', 'Transfer bank', 'Mandiri'],
        ] as [$name, $type, $provider]) {
            PaymentMethod::firstOrCreate(['name' => $name], [
                'code' => 'PM-'.Str::upper(Str::random(12)),
                'type' => $type,
                'provider' => $provider,
                'fee_info' => 'Dikelola manual',
                'is_active' => false,
                'created_by' => $owner->id,
            ]);
        }

        foreach ([
            ['500 Poin', 50000, 500, 0],
            ['1.200 Poin', 100000, 1200, 0],
            ['2.600 Poin', 200000, 2400, 200],
        ] as [$name, $amount, $points, $bonus]) {
            PointBundle::firstOrCreate(['name' => $name], [
                'code' => 'PB-'.Str::upper(Str::random(10)),
                'transfer_amount' => $amount,
                'points' => $points,
                'bonus_points' => $bonus,
                'is_active' => false,
                'created_by' => $owner->id,
            ]);
        }

        foreach (TestPackage::query()->where('status', 'Terbit')->get() as $package) {
            AccessOffer::firstOrCreate(['test_package_id' => $package->id], [
                'code' => 'AO-'.Str::upper(Str::random(10)),
                'product_type' => 'package',
                'title' => $package->name,
                'description' => 'Penawaran contoh. Aktifkan dan tetapkan harga sebelum dijual.',
                'price_points' => 100,
                'attempt_quota' => 1,
                'is_active' => false,
                'created_by' => $owner->id,
            ]);
        }

        foreach ([
            ['demo-active-001', 'Alya Demo', 'alya@example.test', 'SMP Contoh', 'Aktif', 15, 4, 24],
            ['demo-active-002', 'Raka Contoh', 'raka@example.test', 'MTs Simulasi', 'Aktif', 40, 12, 2],
            ['demo-invite-001', 'Naya Simulasi', 'naya@example.test', 'Belum ditentukan', 'Undangan', 0, 0, null],
        ] as [$externalId, $name, $email, $school, $status, $quota, $used, $daysLeft]) {
            StudentAccount::updateOrCreate(['email' => $email], [
                'external_id' => $externalId,
                'name' => $name,
                'school' => $school === 'Belum ditentukan' ? null : $school,
                'status' => $status,
                'attempt_quota' => $quota,
                'attempts_used' => $used,
                'access_expires_at' => $daysLeft === null ? null : now()->addDays($daysLeft),
                'created_by' => $owner->id,
            ]);
        }
    }

    private function seedStaff(string $key, string $role, string $fallbackName): User
    {
        $credentials = config('fibo.'.$key);
        if (empty($credentials['email']) || empty($credentials['password'])) {
            throw new \RuntimeException('FIBO_'.strtoupper($key).'_EMAIL and FIBO_'.strtoupper($key).'_PASSWORD must be set before seeding.');
        }

        return User::updateOrCreate(['email' => $credentials['email']], [
            'name' => $credentials['name'] ?: $fallbackName,
            'role' => $role,
            'password' => $credentials['password'],
            'is_active' => true,
            'must_change_password' => true,
        ]);
    }
}
