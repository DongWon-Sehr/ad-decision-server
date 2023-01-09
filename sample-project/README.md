## 프로젝트 소개
도커 환경에 대한 경험이 부족한 분들을 위해 웹 애플리케이션과 데이터베이스를 docker composer로 실행시키는 방법을 보여주는 예제 프로젝트입니다. 파이썬 서버와 MySQL, Redis가 설정된 상태입니다.

## 도커 설치
[Docker](https://www.docker.com/get-started) 설치 & 로그인 (tested on v4.3.0)

## 서버 띄우기
다음과 같이 make 커맨드를 실행합니다.

```bash
make up
```

## 참고할만한 make 커맨드들
```shell
make build # 도커 이미지 업데이트가 필요한 경우 사용합니다.
make shell # 서버를 띄우지 않고 같은 환경으로 쉘을 띄우고 싶을 때 사용합니다.
make shell-redis # 레디스 컨테이너가 떠 있는 상황에서 실행하면 redis-cli를 띄워줍니다.
make shell-mysql # MySQL 컨테이너가 떠 있는 상황에서 실행하면 mysql shell을 띄워줍니다.
make down # 떠 있는 컨테이너를 모두 내립니다.
```

## 지원하는 API
| 주소 | 설명 |
| - | - |
| http://localhost:8080/hello | Hello world를 출력합니다. |
| http://localhost:8080/mysql-version | MySQL 버전을 출력합니다. |
| http://localhost:8080/redis-version | Redis 버전을 출력합니다. |
