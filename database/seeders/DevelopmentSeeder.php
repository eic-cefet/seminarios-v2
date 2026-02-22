<?php

namespace Database\Seeders;

use App\Enums\CourseSituation;
use App\Models\Course;
use App\Models\PresenceLink;
use App\Models\Rating;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\SeminarLocation;
use App\Models\SeminarType;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserSpeakerData;
use App\Models\UserStudentData;
use App\Models\Workshop;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding development data...');

        // --- Seminar Types ---
        $seminarTypes = collect([
            'Seminário', 'Qualificação', 'Dissertação', 'TCC',
            'Aula inaugural', 'Painel', 'Doutorado',
        ])->map(fn (string $name) => SeminarType::create(['name' => $name]));

        // --- Locations (real CEFET-RJ style rooms) ---
        $locationData = [
            ['name' => 'Auditório Bloco E', 'max_vacancies' => 150],
            ['name' => 'Sala E-301', 'max_vacancies' => 40],
            ['name' => 'Laboratório de Informática 1', 'max_vacancies' => 30],
            ['name' => 'Sala E-205', 'max_vacancies' => 50],
            ['name' => 'Auditório Bloco D', 'max_vacancies' => 100],
        ];
        $locations = collect($locationData)->map(fn (array $data) => SeminarLocation::create($data));

        // --- Courses ---
        $courseNames = [
            'Bacharelado em Ciência da Computação',
            'Bacharelado em Sistemas de Informação',
            'Bacharelado em Engenharia de Computação',
            'Bacharelado em Engenharia Elétrica',
            'Mestrado em Ciência da Computação',
            'Bacharelado em Engenharia de Produção',
        ];
        $courses = collect($courseNames)->map(fn (string $name) => Course::create(['name' => $name]));

        // --- Subjects (real CS/EIC topics) ---
        $subjectNames = [
            'Inteligência Artificial', 'Machine Learning', 'Redes Neurais',
            'Processamento de Linguagem Natural', 'Visão Computacional',
            'Segurança da Informação', 'Criptografia', 'Redes de Computadores',
            'Computação em Nuvem', 'DevOps', 'Engenharia de Software',
            'Banco de Dados', 'Big Data', 'Ciência de Dados',
            'Internet das Coisas', 'Sistemas Embarcados', 'Robótica',
            'Computação Quântica', 'Blockchain', 'Web Development',
            'Mobile Development', 'Microserviços', 'Arquitetura de Software',
            'Teste de Software', 'UX/UI Design', 'Acessibilidade Digital',
            'Ética em IA', 'Processamento de Imagens', 'Computação Gráfica',
            'Sistemas Distribuídos',
        ];
        $subjects = collect($subjectNames)->map(fn (string $name) => Subject::create(['name' => $name]));

        // --- Workshops ---
        $workshopData = [
            ['name' => 'Workshop de Machine Learning Aplicado', 'description' => 'Série de seminários sobre aplicações práticas de Machine Learning na indústria e na pesquisa acadêmica.'],
            ['name' => 'Semana de Segurança Cibernética', 'description' => 'Palestras e demonstrações sobre as últimas tendências em segurança da informação e proteção de dados.'],
            ['name' => 'Jornada de Engenharia de Software', 'description' => 'Seminários sobre boas práticas, arquitetura e tendências em desenvolvimento de software.'],
        ];
        $workshops = collect($workshopData)->map(fn (array $data) => Workshop::create($data));

        $this->command->line('  Created lookup tables');

        // --- Deterministic users for documentation/testing (password: "password") ---
        $docAdmin = User::factory()->admin()->create([
            'name' => 'Administrador',
            'email' => 'admin@cefet-rj.br',
        ]);
        $docTeacher = User::factory()->teacher()->create([
            'name' => 'Professor',
            'email' => 'teacher@cefet-rj.br',
        ]);
        $docStudent = User::factory()->create([
            'name' => 'Estudante',
            'email' => 'student@cefet-rj.br',
        ]);
        UserStudentData::factory()->state([
            'user_id' => $docStudent->id,
            'course_id' => $courses->first()->id,
            'course_situation' => CourseSituation::Studying,
        ])->create();

        $this->command->line('  Created deterministic users: admin@cefet-rj.br, teacher@cefet-rj.br, student@cefet-rj.br');

        // --- Admin users ---
        $adminData = [
            ['name' => 'Carlos Eduardo Lima', 'email' => 'carlos.lima@eic.cefet-rj.br'],
            ['name' => 'Fernanda Oliveira Santos', 'email' => 'fernanda.santos@eic.cefet-rj.br'],
        ];
        $admins = collect($adminData)->map(fn (array $data) => User::factory()->admin()->create($data));
        $admins->push($docAdmin);

        // --- Teacher user ---
        $teacherData = [
            ['name' => 'Prof. Ricardo Almeida', 'email' => 'ricardo.almeida@eic.cefet-rj.br'],
        ];
        $teachers = collect($teacherData)->map(fn (array $data) => User::factory()->teacher()->create($data));
        $teachers->push($docTeacher);

        // --- Speakers (with realistic bios) ---
        $speakerData = [
            ['name' => 'Dr. André Luiz Carvalho', 'institution' => 'UFRJ', 'description' => 'Professor adjunto do Departamento de Ciência da Computação. Pesquisador na área de Inteligência Artificial e Aprendizado de Máquina.'],
            ['name' => 'Dra. Mariana Costa Silva', 'institution' => 'PUC-Rio', 'description' => 'Pesquisadora em Processamento de Linguagem Natural e Linguística Computacional. Doutora em Informática pela PUC-Rio.'],
            ['name' => 'Prof. Roberto Nascimento', 'institution' => 'CEFET-RJ', 'description' => 'Professor do programa de pós-graduação em Ciência da Computação. Especialista em Segurança da Informação.'],
            ['name' => 'Eng. Juliana Ferreira', 'institution' => 'Google Brasil', 'description' => 'Engenheira de Software Sênior. Trabalha com infraestrutura de cloud computing e sistemas distribuídos.'],
            ['name' => 'Dr. Felipe Santos Ribeiro', 'institution' => 'LNCC', 'description' => 'Pesquisador do Laboratório Nacional de Computação Científica. Especialista em Computação de Alto Desempenho.'],
            ['name' => 'Dra. Camila Rodrigues', 'institution' => 'UFF', 'description' => 'Professora associada do Instituto de Computação. Pesquisa em Visão Computacional e Deep Learning.'],
            ['name' => 'Prof. Lucas Mendes Pinto', 'institution' => 'UERJ', 'description' => 'Doutor em Engenharia de Sistemas e Computação. Pesquisa em Internet das Coisas e Sistemas Embarcados.'],
            ['name' => 'Eng. Patrícia Gomes', 'institution' => 'Microsoft Brasil', 'description' => 'Arquiteta de Soluções Cloud. Especialista em Azure e arquitetura de microserviços.'],
            ['name' => 'Dr. Thiago Oliveira Costa', 'institution' => 'IMPA', 'description' => 'Pesquisador em Matemática Aplicada e Ciência de Dados. Doutorado pelo IMPA com foco em otimização.'],
            ['name' => 'Dra. Amanda Souza Lima', 'institution' => 'COPPE/UFRJ', 'description' => 'Professora do Programa de Engenharia de Sistemas e Computação. Pesquisa em Banco de Dados e Big Data.'],
            ['name' => 'Prof. Eduardo Tavares', 'institution' => 'CEFET-RJ', 'description' => 'Professor do Departamento de Informática. Coordenador do grupo de pesquisa em Engenharia de Software.'],
            ['name' => 'Eng. Beatriz Monteiro', 'institution' => 'Nubank', 'description' => 'Tech Lead em Data Engineering. Experiência com pipelines de dados em escala e sistemas de recomendação.'],
            ['name' => 'Dr. Marcos Vinícius Araújo', 'institution' => 'USP', 'description' => 'Professor do IME-USP. Referência em Computação Quântica e Teoria da Computação no Brasil.'],
            ['name' => 'Dra. Isabela Martins', 'institution' => 'Fiocruz', 'description' => 'Pesquisadora em Bioinformática e Computação Aplicada à Saúde. Doutora em Modelagem Computacional.'],
            ['name' => 'Prof. Gabriel Pereira Nunes', 'institution' => 'UNICAMP', 'description' => 'Professor do Instituto de Computação. Pesquisa em Criptografia e Segurança de Protocolos.'],
        ];
        $speakers = collect($speakerData)->map(function (array $data) {
            $user = User::factory()->create([
                'name' => $data['name'],
                'email' => Str::slug($data['name'], '.').'@exemplo.edu.br',
            ]);
            UserSpeakerData::create([
                'user_id' => $user->id,
                'slug' => Str::slug($data['name']).'-'.fake()->unique()->randomNumber(4),
                'institution' => $data['institution'],
                'description' => $data['description'],
            ]);

            return $user;
        });

        // --- Students (real Brazilian names) ---
        $studentNames = [
            'Ana Clara Souza', 'Pedro Henrique Alves', 'Maria Eduarda Lima',
            'João Vitor Nascimento', 'Larissa Fernandes', 'Lucas Gabriel Rocha',
            'Beatriz Cardoso', 'Matheus Silva Santos', 'Isabela Correia',
            'Rafael Augusto Barbosa', 'Gabriela Moreira', 'Gustavo Henrique Pires',
            'Letícia Andrade', 'Bruno Carvalho Melo', 'Sophia Ribeiro',
            'Felipe Costa Azevedo', 'Valentina Mendes', 'Daniel Freitas Lima',
            'Manuela Teixeira', 'Arthur Lopes Martins', 'Clara Duarte',
            'Nicolas Pereira Ramos', 'Helena Gonçalves', 'Vinícius Dias Ferreira',
            'Alice Nogueira', 'Enzo Gabriel Castro', 'Laura Batista Santos',
            'Davi Lucca Araújo', 'Júlia Marques', 'Cauã Rezende',
            'Cecília Farias', 'Leonardo Borges', 'Lívia Campos',
            'Théo Monteiro', 'Yasmin Cavalcanti', 'Samuel Barros',
            'Heloísa Pinto', 'Murilo Cunha', 'Elisa Neves',
            'Henrique Machado', 'Bianca Vieira', 'Bernardo Esteves',
            'Luana Costa', 'Pietro Santana', 'Emanuelly Reis',
            'Caio Dantas', 'Marina Siqueira', 'Otávio Fonseca',
            'Agatha Medeiros', 'Igor Brito',
        ];
        $students = collect($studentNames)->map(fn (string $name) => User::factory()->create([
            'name' => $name,
            'email' => Str::slug($name, '.').'@aluno.cefet-rj.br',
        ]));
        $students->push($docStudent);

        $students->each(function (User $user) use ($courses, $docStudent) {
            if ($user->id === $docStudent->id) {
                return;
            }

            $situation = fake()->randomFloat(2, 0, 1) < 0.95
                ? CourseSituation::Studying
                : CourseSituation::Graduated;

            UserStudentData::factory()
                ->state([
                    'user_id' => $user->id,
                    'course_id' => $courses->random()->id,
                    'course_situation' => $situation,
                ])
                ->create();
        });

        $this->command->line('  Created users: 3 admins, 2 teachers, 15 speakers, 51 students');

        // --- Seminar names (realistic academic titles) ---
        $seminarNames = [
            'Aplicações de Deep Learning em Diagnóstico Médico',
            'Introdução ao Desenvolvimento com React e TypeScript',
            'Segurança em APIs REST: Boas Práticas e Vulnerabilidades',
            'Computação em Nuvem: AWS vs Azure vs GCP',
            'Machine Learning para Previsão de Séries Temporais',
            'Blockchain além das Criptomoedas: Aplicações Empresariais',
            'DevOps na Prática: CI/CD com GitHub Actions',
            'Internet das Coisas: Protocolos e Arquiteturas',
            'Ética e Vieses em Sistemas de Inteligência Artificial',
            'Processamento de Linguagem Natural com Transformers',
            'Arquitetura de Microserviços com Docker e Kubernetes',
            'Big Data: Processamento Distribuído com Apache Spark',
            'Desenvolvimento Mobile com React Native',
            'Testes Automatizados: Do Unitário ao E2E',
            'Redes Neurais Convolucionais para Classificação de Imagens',
            'Bancos de Dados NoSQL: Quando e Por Que Usar',
            'Criptografia Pós-Quântica: O Futuro da Segurança',
            'UX Research: Métodos Quantitativos e Qualitativos',
            'Programação Funcional com Elixir e Phoenix',
            'Robótica Educacional: Arduino e Raspberry Pi',
            'Análise de Sentimentos em Redes Sociais',
            'GraphQL vs REST: Comparação Prática',
            'Computação Quântica: Fundamentos e Perspectivas',
            'Acessibilidade Web: WCAG e Boas Práticas',
            'Data Engineering: Construindo Pipelines de Dados',
            'Sistemas Distribuídos: Consenso e Tolerância a Falhas',
            'Visão Computacional Aplicada à Indústria 4.0',
            'Clean Architecture em Projetos Laravel',
            'Bioinformática: Análise Computacional de Genomas',
            'Observabilidade: Métricas, Logs e Traces',
        ];

        $seminarDescriptions = [
            "Este seminário aborda as técnicas mais recentes e seus impactos na área. Serão apresentados estudos de caso e demonstrações práticas.\n\nOs participantes terão a oportunidade de discutir os desafios e as oportunidades no campo.",
            "Apresentação dos fundamentos teóricos seguida de exemplos práticos de implementação. O seminário inclui uma sessão de perguntas e discussão aberta.\n\nIndicado para estudantes e profissionais que desejam aprofundar seus conhecimentos na área.",
            "Uma visão geral das tendências atuais e futuras, com foco em aplicações reais e resultados de pesquisa recentes do grupo de estudos.\n\nO objetivo é apresentar o estado da arte e discutir direções de pesquisa promissoras.",
        ];

        // --- Weighted seminar type distribution ---
        $typeWeights = [
            'TCC' => 15, 'Seminário' => 8, 'Dissertação' => 3,
            'Qualificação' => 3, 'Doutorado' => 1,
        ];
        $weightedTypes = [];
        foreach ($typeWeights as $name => $weight) {
            $type = $seminarTypes->firstWhere('name', $name);
            for ($i = 0; $i < $weight; $i++) {
                $weightedTypes[] = $type->id;
            }
        }

        // --- Seminars ---
        $seminarIndex = 0;

        // 20 past seminars
        $pastSeminars = collect();
        for ($i = 0; $i < 20; $i++) {
            $name = $seminarNames[$seminarIndex % count($seminarNames)];
            $seminar = Seminar::factory()
                ->past()
                ->state([
                    'name' => $name,
                    'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(4),
                    'description' => $seminarDescriptions[$i % count($seminarDescriptions)],
                    'seminar_location_id' => $locations->random()->id,
                    'seminar_type_id' => fake()->randomElement($weightedTypes),
                    'workshop_id' => $i < 2 ? $workshops->random()->id : null,
                    'created_by' => $teachers->merge($admins)->random()->id,
                ])
                ->create();

            $this->attachSpeakersAndSubjects($seminar, $speakers, $subjects);
            PresenceLink::factory()->inactive()->create(['seminar_id' => $seminar->id]);
            $pastSeminars->push($seminar);
            $seminarIndex++;
        }

        // 8 upcoming seminars
        $upcomingSeminars = collect();
        for ($i = 0; $i < 8; $i++) {
            $name = $seminarNames[$seminarIndex % count($seminarNames)];
            $seminar = Seminar::factory()
                ->upcoming()
                ->state([
                    'name' => $name,
                    'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(4),
                    'description' => $seminarDescriptions[$i % count($seminarDescriptions)],
                    'seminar_location_id' => $locations->random()->id,
                    'seminar_type_id' => fake()->randomElement($weightedTypes),
                    'created_by' => $teachers->merge($admins)->random()->id,
                ])
                ->create();

            $this->attachSpeakersAndSubjects($seminar, $speakers, $subjects);
            PresenceLink::factory()->create(['seminar_id' => $seminar->id]);
            $upcomingSeminars->push($seminar);
            $seminarIndex++;
        }

        // 2 inactive seminars
        for ($i = 0; $i < 2; $i++) {
            $name = $seminarNames[$seminarIndex % count($seminarNames)];
            $seminar = Seminar::factory()
                ->upcoming()
                ->inactive()
                ->state([
                    'name' => $name,
                    'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(4),
                    'description' => $seminarDescriptions[$i % count($seminarDescriptions)],
                    'seminar_location_id' => $locations->random()->id,
                    'seminar_type_id' => fake()->randomElement($weightedTypes),
                    'created_by' => $teachers->merge($admins)->random()->id,
                ])
                ->create();

            $this->attachSpeakersAndSubjects($seminar, $speakers, $subjects);
            PresenceLink::factory()->inactive()->create(['seminar_id' => $seminar->id]);
            $seminarIndex++;
        }

        $this->command->line('  Created 30 seminars (20 past, 8 upcoming, 2 inactive)');

        // --- Registrations & Ratings ---
        $ratingComments = [
            'Excelente apresentação, muito didática!',
            'Conteúdo muito relevante para minha pesquisa.',
            'Palestrante domina muito bem o assunto.',
            'Gostaria que tivesse durado mais tempo.',
            'Ótima oportunidade de aprendizado.',
            'Muito bom, recomendo para todos os colegas.',
            'O tema é muito atual e a abordagem foi perfeita.',
            'Aprendi bastante, especialmente na parte prática.',
            null, null, null, null, null,
        ];

        $registrationCount = 0;
        $ratingCount = 0;

        foreach ($pastSeminars as $seminar) {
            $attendees = $students->random(fake()->numberBetween(8, 20));

            foreach ($attendees as $student) {
                $isPresent = fake()->randomFloat(2, 0, 1) < 0.57;

                Registration::factory()->state([
                    'seminar_id' => $seminar->id,
                    'user_id' => $student->id,
                    'present' => $isPresent,
                    'reminder_sent' => true,
                    'certificate_code' => $isPresent ? fake()->uuid() : null,
                    'certificate_sent' => $isPresent && fake()->boolean(70),
                ])->create();
                $registrationCount++;

                if ($isPresent && fake()->randomFloat(2, 0, 1) < 0.30) {
                    $score = fake()->randomElement([5, 5, 5, 5, 5, 5, 5, 5, 4, 3]);

                    Rating::factory()->create([
                        'seminar_id' => $seminar->id,
                        'user_id' => $student->id,
                        'score' => $score,
                        'comment' => fake()->randomElement($ratingComments),
                    ]);
                    $ratingCount++;
                }
            }
        }

        foreach ($upcomingSeminars as $seminar) {
            $attendees = $students->random(fake()->numberBetween(10, 25));

            foreach ($attendees as $student) {
                Registration::factory()->create([
                    'seminar_id' => $seminar->id,
                    'user_id' => $student->id,
                ]);
                $registrationCount++;
            }
        }

        $this->command->line("  Created {$registrationCount} registrations, {$ratingCount} ratings");
        $this->command->info('Development data seeded successfully.');
    }

    private function attachSpeakersAndSubjects(Seminar $seminar, $speakers, $subjects): void
    {
        $speakerCount = fake()->randomElement([1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 2, 2, 2, 2, 3]);
        $seminar->speakers()->attach(
            $speakers->random(min($speakerCount, $speakers->count()))->pluck('id')
        );

        $subjectCount = fake()->numberBetween(1, 4);
        $seminar->subjects()->attach(
            $subjects->random(min($subjectCount, $subjects->count()))->pluck('id')
        );
    }
}
