<style>
.tree0 {
    margin-left: 0em;
}
.tree1 {
    margin-left: 1em;
}
.tree2 {
    margin-left: 2em;
}
.tree3 {
    margin-left: 3em;
}
.tree4 {
    margin-left: 4em;
}
.tree5 {
    margin-left: 5em;
}

</style>
<script type="text/javascript">
var tree = [
    {
        nickname: "FDIC",
        label: "FDIC - Federal Deposit Insurance Corporation",
        counts: [0, 0, 0, 0, 0, 0, 0, 0, 0],
        children: [
            {
                nickname: "4C",
                label: "4C - Communication, Challenge, Control, and Capabilities",
                counts: [0, 0, 0, 0, 0, 0, 0, 0, 0],
                children: [
                    {
                        nickname: "BBCC",
                        label: "BBCC - Best Bank Credit Card System",
                        counts: [0, 0, 0, 0, 0, 0, 0, 0, 0],
                    },
                    {
                        nickname: "CTM",
                        label: "CTM  - Control Totals Module",
                        counts: [0, 0, 0, 0, 0, 0, 0, 0, 0],
                    },
                    {
                        nickname: "INTRALINKS",
                        label: "INTRALINKS - INTRALINKS",
                        counts: [0, 0, 0, 0, 0, 0, 0, 0, 0],
                    },
                    {
                        nickname: "METIN",
                        label: "METIN - Metavante Insight System",
                        counts: [0, 0, 0, 0, 0, 0, 0, 0, 0],
                    },
                    {
                        nickname: "NAIS",
                        label: "NAIS - Natioanl Asset Inventory System",
                        counts: [0, 0, 0, 0, 0, 0, 0, 0, 0],
                    },
                    {
                        nickname: "SMADS",
                        label: "SMADS - Securities Management and Disposition System",
                        counts: [0, 0, 0, 0, 0, 0, 0, 0, 0],
                    },
                    {
                        nickname: "WRAPS",
                        label: "WRAPS - Warranties and Reps Accts Processing System",
                        counts: [0, 0, 0, 0, 0, 0, 0, 0, 0],
                    }
                ]
            },
            {
                nickname: "AIMS",
                label: "AIMS - Assessment Information Management System",
                counts: [0, 0, 0, 0, 0, 0, 0, 0, 0]
            },
            {
                nickname: "CHRIS",
                label: "CHRIS - Corporate Human Resources Information System",
                counts: [0, 0, 0, 0, 0, 0, 0, 0, 0],
                children: [
                    {
                        nickname: "BTS",
                        label: "BTS - Buyout Tracking System",
                        counts: [0, 0, 0, 0, 0, 0, 0, 0, 0],
                    },
                    {
                        nickname: "CHRIS TA",
                        label: "CHRIS TA - CHRIS Time and Attendance",
                        counts: [0, 0, 0, 0, 0, 0, 0, 0, 0],
                    },
                    {
                        nickname: "MYENROLL.COM",
                        label: "MYENROLL.COM - Myenroll.com",
                        counts: [0, 0, 0, 0, 0, 0, 0, 0, 0],
                    },
                    {
                        nickname: "NFC",
                        label: "NFC - National Finance Center",
                        counts: [0, 0, 0, 0, 0, 0, 0, 0, 0],
                    },
                    {
                        nickname: "PEGASYS",
                        label: "PEGASYS - PEGASYS",
                        counts: [0, 0, 0, 0, 0, 0, 0, 0, 0],
                    }
                ]
            }
        ]
    }
]

function render(systems, depth) {
    var t = document.getElementById('haase');
    for (var a in systems) {
        s = systems[a];
        var newRow = t.insertRow(t.rows.length);
        newRow.id = s.nickname;
        newRow.expanded = false;
        if (depth > 1) {
            newRow.style.display = "none";
        } else if (depth < 1) {
            newRow.expanded = true;
        }
        var cell = newRow.insertCell(0);
        cell.innerHTML = "<span class=\"tree" + depth + "\"<a href='#' onclick='toggle(\"" + s.nickname + "\")'><img id=\"" + s.nickname + "Img\" src=\"/images/plus.png\"></a>" + s.label;
        var i = 1;
        for (var c in s.counts) {
            cell = newRow.insertCell(i++);
            cell.appendChild(document.createTextNode("" + s.counts[c]));
        }
        if (typeof s.children != 'undefined') {
            if (depth > 1) return;
            render(s.children, depth+1);
        }
    }
}

function toggle(n) {
    system = find(n, tree);
    if (system.expanded == true) {
        // hide child nodes
        document.getElementById(system.nickname + "Img").src = "/images/plus.png";
        hide(system.children);
        system.expanded = false;
    } else {
        document.getElementById(system.nickname + "Img").src = "/images/minus.png";
        show(system.children);
        system.expanded = true;
    }
}

function hide(node) {
    for (sys in node) {
        s = node[sys];
        s.expanded = false;
        document.getElementById(s.nickname).style.display = 'none';
        if (typeof s.children != 'undefined') {
            hide (s.children);
        }
    }
}

function show(node) {
    for (sys in node) {
        s = node[sys];
        s.exanded = true;
        document.getElementById(s.nickname).style.display = 'table-row';
        if (typeof s.children != 'undefined') {
            hide (s.children);
        }
    }   
}

function find(nick, t) {
    for (var n in t) {
        sys = t[n];
        if (t[n].nickname == nick) {
            return t[n];
        } else if (typeof sys.children != 'undefined') {
            var f = find(nick, sys.children);
            if (f != false) {
                return f;
            }
        }
    }
    return false;
}

function dump(a) {
    var s = "";
    for (var b in a) {
        s += b + ":" + a[b] + "\n";
    }
    alert (s);
}

</script>
</head>
<body onload="render(tree, 0)">
<table border="1" id='haase'>
    <tr>
        <th>NAME</th><th>NEW</th><th>DRAFT</th><th>MITIGATION ISSO</th>
        <th>MITIGATION IVV</th><th>EN</th><th>EVIDENCE ISSO</th>
        <th>EVIDENCE IVV</th><th>CLOSED</th><th>TOTAL</th>
    </tr>
</table>
