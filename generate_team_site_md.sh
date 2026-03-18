#!/usr/bin/env bash
set -euo pipefail

CSV_INPUT="${1:-Phase2.csv}"
USER_MAP_INPUT="${2:-user_team_map.csv}"
TEAM_IP_INPUT="${3:-team_site_ip.csv}"
OUTPUT_MD="${4:-team_site_ips.md}"
EXPECTED_TEAMS="${5:-19}"

if [[ ! -f "$CSV_INPUT" ]]; then
    echo "CSV file not found: $CSV_INPUT" >&2
    exit 1
fi

if [[ ! -f "$USER_MAP_INPUT" ]]; then
    echo "User mapping file not found: $USER_MAP_INPUT" >&2
    exit 1
fi

if [[ ! -f "$TEAM_IP_INPUT" ]]; then
    echo "Team IP mapping file not found: $TEAM_IP_INPUT" >&2
    exit 1
fi

awk -F',' -v expected_teams="$EXPECTED_TEAMS" '
BEGIN {
    OFS = "|"
}

FILENAME == ARGV[1] {
    if (FNR == 1) next

    team_no = $1
    site_ip = $2
    gsub(/^[[:space:]]+|[[:space:]]+$/, "", team_no)
    gsub(/^[[:space:]]+|[[:space:]]+$/, "", site_ip)

    if (team_no != "") {
        team_ip_map[team_no] = site_ip
        all_teams[team_no] = 1
    }
    next
}

FILENAME == ARGV[2] {
    if (FNR == 1) next

    uname = tolower($1)
    team_no = $2

    gsub(/^[[:space:]]+|[[:space:]]+$/, "", uname)
    gsub(/^[[:space:]]+|[[:space:]]+$/, "", team_no)

    if (uname != "") {
        user_team_map[uname] = team_no
    }
    next
}

FILENAME == ARGV[3] {
    if (FNR == 1) {
        print "# Team Site IP Roster"
        print ""
        print "- Expected total teams: " expected_teams
        print "- Source accounts file: `" ARGV[3] "`"
        print "- Team IP file: `" ARGV[1] "`"
        print "- User-team mapping file: `" ARGV[2] "`"
        print ""
        print "## Team Site IPs"
        print ""
        print "| Team No. | Site IP |"
        print "|---|---|"

        for (i = 1; i <= expected_teams; i++) {
            team_key = i ""
            ip = team_ip_map[team_key]
            if (ip == "") {
                ip = "TBD"
                missing_team_ip++
            }
            print "| Team " team_key " | " ip " |"
        }

        print ""
        print "## User Roster"
        print ""
        print "| Username | Name | Team No. | Site IP |"
        print "|---|---|---|---|"
        next
    }

    username = $1
    name = $3

    uname_key = tolower(username)
    gsub(/^[[:space:]]+|[[:space:]]+$/, "", uname_key)
    gsub(/^[[:space:]]+|[[:space:]]+$/, "", username)
    gsub(/^[[:space:]]+|[[:space:]]+$/, "", name)

    if (username == "") next

    team_no = user_team_map[uname_key]
    if (team_no == "") {
        team_display = "TBD"
        site_ip = "TBD"
        pending_team++
        pending_ip++
    } else {
        team_display = "Team " team_no
        site_ip = team_ip_map[team_no]
        if (site_ip == "") {
            site_ip = "TBD"
            pending_ip++
        }
        teams_seen[team_no] = 1
    }

    gsub(/\|/, "\\\\|", name)

    print "| " username " | " name " | " team_display " | " site_ip " |"
    total_users++
}

END {
    unique_team_count = 0
    for (t in teams_seen) {
        unique_team_count++
    }

    print ""
    print "## Summary"
    print ""
    print "- Total users listed: " total_users
    print "- Users with known team assignment: " (total_users - pending_team)
    print "- Users missing team assignment: " pending_team
    print "- Users missing site IP: " pending_ip
    print "- Team IP entries available: " (expected_teams - missing_team_ip) " / " expected_teams
    print "- Teams currently represented in user mapping: " unique_team_count " / " expected_teams
}
' "$TEAM_IP_INPUT" "$USER_MAP_INPUT" "$CSV_INPUT" > "$OUTPUT_MD"

echo "Generated $OUTPUT_MD"
