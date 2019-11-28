<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\SignUpRequest;
use App\Project;
use App\Rol;
use App\Tag;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', [
            'except' => [
                'login', 'signin',
                'project', 'projects',
                'users', 'roles', 'user', 'update', 'getProject',
                'deleteProject', 'deleteUser', 'me', 'getTags', 'deleteTag', 'tag',
                'getEmployees', 'getProjectMembers', 'getProjectTags', 'deleteMember', 'addMember',
                'activity', 'getProjectActivities', 'updateActivityState'
            ]
        ]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function signin(SignUpRequest $request)
    {
        $data = $request->all();
        User::create($data);
        return $this->login($request);
    }

    public function update(Request $request)
    {
        $data = $request->all();
        $user = User::find($data['iduser']);
        unset($data['iduser']);
        if ($user != null) {
            if ($user->password == $data['password']) {
                unset($data['password']);
            }
            foreach ($data as $property => $value) {
                $user->{$property} = $value;
            }
            $user->avatar = 'avatar';
            $user->save();
        }

        return response()->json([
            'user' => $data
        ]);
    }

    public function project(Request $request)
    {
        $data = $request->all();
        if (array_key_exists('idproject', $data)) {
            $project = Project::find($data['idproject']);
            if ($project != null) {
                unset($data['idproject']);
                foreach ($data as $property => $value) {
                    $project->{$property} = $value;
                }
                $project->startdate = date('Y-m-d');
                $project->save();
            }
        } else {
            Project::create($data);
        }

        return response()->json([
            'msg' => 'success'
        ]);
    }

    public function projects(Request $request)
    {
        return response()->json([
            'projects' => Project::all()
        ]);
    }

    public function getProject($id)
    {
        return response()->json([
            'project' => Project::find($id)
        ]);
    }

    public function users(Request $request)
    {
        return response()->json([
            'users' => User::all()
        ]);
    }

    public function roles(Request $request)
    {
        return response()->json([
            'roles' => Rol::all()
        ]);
    }

    public function user($id)
    {
        return response()->json([
            'user' => User::find($id)
        ]);
    }

    public function deleteProject($id)
    {
        $project = Project::find($id);
        return response()->json([
            'project' => $project->delete()
        ]);
    }

    public function deleteUser($id)
    {
        $user = User::find($id);
        return response()->json([
            'user' => $user->delete()
        ]);
    }

    public function tag(Request $request)
    {
        $data = $request->all();
        unset($data['projectName']);
        if (array_key_exists('idtag', $data)) {
            $tag = Tag::find($data['idtag']);
            if ($tag != null) {
                unset($data['idtag']);
                foreach ($data as $property => $value) {
                    $tag->{$property} = $value;
                }
                $tag->save();
            }
        } else {
            Tag::create($data);
        }
        return response()->json([
            'msg' => 'success'
        ]);
    }

    public function getTags($id = null)
    {
        $query = DB::table('tags')
            ->join('projects', 'tags.idproject', '=', 'projects.idproject');
        if ($id != null && $id > 0) {
            $query->where('tags.idtag', '=', $id);
        }
        $query->select('tags.idtag AS idtag', 'tags.idproject AS idproject', 'tags.name AS name', 'tags.state AS state', 'projects.name AS projectName', 'tags.color');
        return response()->json([
            'tagsList' => $query->get(),
            'tag' => $query->first(),
        ]);
    }

    public function getProjectTags($id)
    {
        return response()->json([
            'list' => Tag::where('idproject', '=', $id)->get()
        ]);
    }

    public function getEmployees()
    {
        return response()->json([
            'list' => User::where('idrol', 2)->get()
        ]);
    }

    public function getProjectMembers($id)
    {
        return response()->json([
            'list' => DB::table('projects AS p')
                ->join('user_project AS up', 'p.idproject', '=', 'up.idproject')
                ->join('users AS u', 'up.iduser', '=', 'u.iduser')
                ->where('p.idproject', '=', $id)
                ->get()
        ]);
    }

    public function addMember($id, $personid)
    {
        $added = false;
        $idUserProjectList = DB::table('user_project AS up')
            ->where('up.iduser', '=', $personid)
            ->where('up.idproject', '=', $id)
            ->select('up.iduser_project AS id')
            ->pluck('id');
        if ($idUserProjectList->count() == 0) {
            DB::table('user_project')->insert([
                'iduser' => $personid,
                'idproject' => $id,
                'owner' => 0
            ]);
            $added = true;
        }

        return response()->json([
            'added' => $added
        ]);
    }

    public function deleteMember($id, $personid)
    {
        $deleted = false;
        $idUserProjectList = DB::table('user_project AS up')
            ->where('up.iduser', '=', $personid)
            ->where('up.idproject', '=', $id)
            ->select('up.iduser_project AS id')
            ->pluck('id');

        if ($idUserProjectList->count() > 0) {
            $userProjectActivitiesList = DB::table('user_proj_act AS upa')
                ->whereIn('iduser_project', $idUserProjectList);
            if ($userProjectActivitiesList->count() == 0) {
                DB::table('user_project')
                    ->whereIn('iduser_project', $idUserProjectList)
                    ->delete();
                $deleted = true;
            }
        }
        return response()->json([
            'id' => $id,
            'personid' => $personid,
            'count' => $idUserProjectList->count(),
            // 'count2' => $userProjectActivitiesList->count(),
            'deleted' => $deleted
        ]);
    }

    public function activity(Request $request)
    {
        $success = true;
        $tagsid = $request->tagsid;
        $activity = $request->activity;
        $personsid = $request->personsid;
        $projectid = $request->projectid;

        $idUserProjectList = DB::table('user_project AS up')
            ->where('up.idproject', '=', $projectid)
            ->select('up.iduser_project AS id')
            ->pluck('id');

        if (!array_key_exists('idactivity', $activity)) {
            $idActivity = DB::table('activities')->insertGetId($activity);
        } else {
            $idActivity = $activity['idactivity'];
            unset($activity['idactivity']);
            DB::table('activities')
                ->where('idactivity', $idActivity)
                ->update($activity);
        }

        DB::table('activity_tag')->where('idactivity', '=', $idActivity)->delete();
        $inserts = [];
        foreach ($tagsid as $tag) {
            $inserts[] = [
                'idactivity' => $idActivity,
                'idtag' => $tag
            ];
        }
        DB::table('activity_tag')->insert($inserts);

        DB::table('user_proj_act')
            ->whereIn('iduser_project', $idUserProjectList)
            ->where('idactivity', '=', $idActivity)
            ->delete();

        $idUserProjectList = DB::table('user_project AS up')
            ->where('up.idproject', '=', $projectid)
            ->whereIn('up.iduser', $personsid)
            ->select('up.iduser_project AS id')
            ->pluck('id');

        $inserts = [];
        foreach ($idUserProjectList as $person) {
            $inserts[] = [
                'iduser_project' => $person,
                'idactivity' => $idActivity
            ];
        }
        DB::table('user_proj_act')->insert($inserts);

        return response()->json([
            'added' => $success
        ]);
    }

    public function getProjectActivities($idproject)
    {
        $idUserProjectList = DB::table('user_project AS up')
            ->where('up.idproject', '=', $idproject)
            ->select('up.iduser_project AS id')
            ->pluck('id');

        $idActivities = DB::table('user_proj_act AS upa')
            ->whereIn('upa.iduser_project', $idUserProjectList)
            ->select('upa.idactivity AS id')
            ->groupBy('upa.idactivity')
            ->pluck('id');

        $results = DB::table('activities AS a')
            ->whereIn('a.idactivity', $idActivities)
            ->select('a.idactivity', 'a.name', 'a.state', 'a.order')
            ->orderBy('a.order');

        $tags = DB::table('activity_tag AS at')
            ->whereIn('at.idactivity', $idActivities)
            ->get();

        $results = $results->get();
        $todo = [];
        $done = [];
        $checked = [];
        foreach ($results as $activity) {
            if ($activity->state == 1) {
                $todo[] = $activity;
            }
            if ($activity->state == 2) {
                $done[] = $activity;
            }
            if ($activity->state == 3) {
                $checked[] = $activity;
            }
        }
        return response()->json([
            'todo' => $todo,
            'done' => $done,
            'checked' => $checked,
            'tags' => $tags,
            'results' => $idActivities
        ]);
    }

    public function deleteTag($id)
    {
        return response()->json([
            'user' => DB::table('tags')->where('idtag', '=', $id)->delete()
        ]);
    }

    public function updateActivityState($idactivity, $state)
    {
        return response()->json([
            'res' => DB::table('activities')
                ->where('idactivity', $idactivity)
                ->update([
                    'state' => $state
                ])
        ]);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => auth()->user()
        ]);
    }
}
